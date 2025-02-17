const { Application } = Shopware;
const { cloneDeep, merge } = Shopware.Utils.object;
const Criteria = Shopware.Data.Criteria;
const { warn } = Shopware.Utils.debug;

Application.addServiceProvider('cmsDataResolverService', () => {
    return {
        resolve,
    };
});

let repoFactory = null;
let cmsService = null;
let cmsElements = null;
let contextService = null;
const repositories = {};
const slots = {};

async function resolve(page) {
    const loadedData = [];
    const slotEntityList = {};

    contextService = Shopware.Context.api;
    repoFactory = Shopware.Service('repositoryFactory');
    cmsService = Shopware.Service('cmsService');
    cmsElements = cmsService.getCmsElementRegistry();

    page.sections.forEach((section) => {
        initVisibility(section);

        section.blocks.forEach((block) => {
            initVisibility(block);

            block.slots.forEach((slot) => {
                slots[slot.id] = slot;
                const cmsElement = cmsElements[slot.type];

                if (!cmsElement) {
                    warn(`Missing registration for slot type ${slot.type}.
                        Slot ${slot.id} Block ${block.name} (${block.id}) Section ${section.name} (${section.id})`);
                    return;
                }

                initSlotConfig(slot);
                initSlotDefaultData(slot);

                const slotData = cmsElement.collect(slot);

                if (Object.keys(slotData).length > 0) {
                    slotEntityList[slot.id] = slotData;
                }
            });
        });
    });

    const { directReads, searches } = optimizeCriteriaObjects(slotEntityList);

    loadedData.push(
        fetchByIdentifier(directReads),
    );

    loadedData.push(
        fetchByCriteria(searches),
    );

    // Internal promises are allowed to fail, no need to catch
    return Promise.all(loadedData).then(([readResults, searchResults]) => {
        Object.entries(slotEntityList).forEach(([slotId, slotEntityData]) => {
            const slot = slots[slotId];
            const slotEntities = [];

            Object.entries(slotEntityData).forEach(([searchKey, slotData]) => {
                if (canBeMerged(slotData)) {
                    slotEntities[searchKey] = readResults[slotData.name];
                } else {
                    slotEntities[searchKey] = searchResults[slotId][searchKey];
                }
            });

            const cmsElement = cmsElements[slot.type];

            if (cmsElement) {
                cmsElement.enrich(slot, slotEntities);
            }
        });

        return true;
    });
}

function initVisibility(element) {
    if (!element.visibility) {
        element.visibility = {};
    }

    const visibilityProperties = ['mobile', 'tablet', 'desktop'];

    visibilityProperties.forEach((key) => {
        if (typeof element.visibility[key] === 'boolean') {
            return;
        }

        element.visibility[key] = true;
    });
}


/**
 * @private
 * @package buyers-experience
 */
function initSlotConfig(slot) {
    const slotConfig = cmsElements[slot.type];
    const defaultConfig = slotConfig.defaultConfig || {};

    slot.config = merge(cloneDeep(defaultConfig), slot.translated.config || {});
}

/**
 * @private
 * @package buyers-experience
 */
function initSlotDefaultData(slot) {
    const slotConfig = cmsElements[slot.type];
    const defaultData = slotConfig.defaultData || {};

    slot.data = merge(cloneDeep(defaultData), slot.data || {});
}

/**
 * @private
 * @package buyers-experience
 */
function optimizeCriteriaObjects(slotEntityCollection) {
    const directReads = {};
    const searches = {};

    Object.entries(slotEntityCollection).forEach(([slotId, criteriaList]) => {
        Object.entries(criteriaList).forEach(([searchKey, entity]) => {
            if (canBeMerged(entity)) {
                if (!directReads[entity.name]) {
                    directReads[entity.name] = [];
                }

                const entityId = Array.isArray(entity.value) ? entity.value : [entity.value];

                directReads[entity.name].push(...entityId);
            } else {
                if (!searches[slotId]) {
                    searches[slotId] = { [searchKey]: [] };
                }

                searches[slotId][searchKey] = entity;
            }
        });
    });

    return {
        directReads,
        searches,
    };
}

/**
 * @private
 * @package buyers-experience
 */
function canBeMerged(entity) {
    if (!entity.searchCriteria) {
        return true;
    }

    const criteria = entity.searchCriteria;

    if (criteria.associations.length > 0) {
        return false;
    }

    if (criteria.filters.length > 0) {
        return false;
    }

    if (criteria.term) {
        return false;
    }

    return criteria.sortings.length <= 0;
}

async function fetchByIdentifier(directReads) {
    const entities = {};
    const fetchPromises = [];

    Object.entries(directReads).forEach(([entityName, entityIds]) => {
        if (entityIds.length > 0) {
            const criteria = new Criteria(1, 25);
            criteria.setIds(entityIds);

            const repo = getRepository(entityName);
            if (!repo) {
                return;
            }

            fetchPromises.push(
                repo.search(criteria, contextService).then((response) => {
                    entities[entityName] = response;
                }),
            );
        }
    });

    await Promise.allSettled(fetchPromises);
    return entities;
}

/**
 * @private
 * @package buyers-experience
 */
async function fetchByCriteria(searches) {
    const results = {};
    const fetchPromises = [];

    Object.keys(searches).forEach((slotId) => {
        const criteriaList = searches[slotId];
        results[slotId] = {};

        Object.keys(criteriaList).forEach((searchKey) => {
            const entity = criteriaList[searchKey];
            if (!entity.searchCriteria) {
                return;
            }

            const criteria = entity.searchCriteria;

            const repo = getRepository(entity.name);
            if (!repo) {
                return;
            }

            const context = entity.context || contextService;

            fetchPromises.push(
                repo.search(criteria, context).then((response) => {
                    results[slotId][searchKey] = response;
                }),
            );
        });
    });

    await Promise.allSettled(fetchPromises);
    return results;
}

/**
 * @private
 * @package buyers-experience
 */
function getRepository(entity) {
    if (repositories[entity]) {
        return repositories[entity];
    }

    try {
        repositories[entity] = repoFactory.create(entity);
    } catch (exception) {
        warn('cmsDataResolverService', exception.message);

        return null;
    }

    return repositories[entity];
}
