import {useEffect, useRef, useState, useImperativeHandle, forwardRef} from "react";
import {__} from "@wordpress/i18n";
import Tooltip from "../../_components/Tooltip";
import SelectField from "./fields/Select";
import MediaUploader from "./utilities/MediaUploader";
import {
    createMapping,
    createMappingValues,
    getMappingValues,
    updateMapping,
    updateMappingValues,
    mappingMetaBatch
} from "../../helpers/rest";
import {dataValue, dataValueToSelectValue, optionValue} from "../helpers/helper";
import LanguageDropdown from "./utilities/LanguageDropdown";

const Entry = forwardRef(({index, data, defaultObjects, updateEntry, deleteEntry, deleteRow, rendered, openByDefault, isPremium, upgradeUrl, restUrl, restNonce, valuesPerPage, languages, isMultilingual, debug}, ref) => {
    const [open, setOpen] = useState(openByDefault);
    const [host, setHost] = useState('');
    const [path, setPath] = useState('');
    const [valuesPaged, setValuesPaged] = useState(1);
    const [totalValues, setTotalValues] = useState(0);
    const [mappingValues, setMappingValues] = useState([]);
    const newMappingValues = useRef([]);
    const [optionsLoaded, setOptionsLoaded] = useState(false);
    const [customHtml, setCustomHtml] = useState('');
    const [favicon, setFavicon] = useState({id: false});
    const allMappingValues = useRef([]);
    const [selectedLocale, setSelectedLocale] = useState('');
    const [customBody, setCustomBody] = useState('');
    // Handel change of the entry
    const changed = useRef({
        host: false,
        path: false,
        mappingValues: false,
        customHtml: false,
        favicon: false,
        locale: false,
        customBody: false,
    });

    useImperativeHandle(ref, () => ({
        save() {
            if (Object.values(changed.current).some(c => c)) {
                // Requires at least one option
                if (!newMappingValues.current.length) {
                    return new Promise((resolve) => {
                        resolve({
                            type: 'error',
                            message: __("Each domain requires at least one mapping.", 'domain-mapping-system'),
                            info: `${host}${path ? '/' + path : ''}`,
                            hasNoMappings: true,
                        });
                    });
                } else {
                    // Check homepage for primary
                    // const i = newMappingValues.current.findIndex(value => value.type === 'posts_homepage');
                    // if (i !== -1 && !newMappingValues.current[i].primary) {
                    //     return new Promise((resolve) => {
                    //         resolve({
                    //             type: 'error',
                    //             message: __("The home page should be the main one.", 'domain-mapping-system'),
                    //             info: `${host}${path ? '/' + path : ''}`,
                    //             homepageNonPrimary: true,
                    //         });
                    //     });
                    // }
                }
                // Create/update
                return !data.mapping?.id ? create() : update();
            } else {
                return new Promise((resolve) => {
                    resolve({
                        type: 'success',
                        message: __("Item saved successfully!", 'domain-mapping-system'),
                    });
                });
            }
        },
    }));

    useEffect(() => {
        // Set mapping
        if (data.mapping) {
            setHost(data.mapping.host || '');
            setPath(data.mapping.path || '');
            setCustomHtml(data.mapping.custom_html || '');
            setFavicon({
                id: data.mapping.attachment_id || false,
                src: data._links?.attachment_url || '',
            });
        }
        if (isMultilingual && data._mapping_meta?.length) {
            const locales = data._mapping_meta.filter(meta => meta.key === 'locale');
            if (locales.length > 0) {
                const locale = locales[0].value;
                setSelectedLocale(locale);
            }
        }
        if (data._mapping_meta?.length) {
            const customBody = data._mapping_meta.filter(meta => meta.key === 'custom_body');
            if (customBody.length > 0) {
                setCustomBody(customBody[0].value);
            }
        }
        // Set mapping values
        if (data._values?.items?.length) {
            const values = isPremium ? data._values.items : data._values.items.filter(mv => mv.value.primary);
            setMappingValues(values);
            setTotalValues(isPremium ? +data._values._total : 1);
        }
        setOptionsLoaded(true);
    }, [data, isMultilingual, isPremium]);

    /**
     * Create mapping with values
     *
     * @return {Promise<{type: string, message: string, info: string|undefined}>}
     */
    const create = () => {
        return createMapping(restUrl, restNonce, {favicon, customHtml, host, path}).then(res => {
            // Update entry
            updateEntry(res, data.uniqueKey);
            return createMapMeta(res.mapping.id).catch(metaError => {
                debug && console.error(metaError);
                return {
                    type: 'error',
                    message: __("Failed to update metadata.", 'domain-mapping-system'),
                    info: `${host}${path ? '/' + path : ''}`,
                };
            }).then(data => {
                // Create values
                return createMappingValues(restUrl, restNonce, res.mapping.id, newMappingValues.current).then(res2 => {
                    const newData = JSON.parse(JSON.stringify(mappingValues));
                    for (const methodData of res2) {
                        if (!methodData.data?.length) {
                            continue;
                        }
                        if (methodData.method === 'create') {
                            // Add new data
                            newData.push(...methodData.data);
                        }
                    }
                    // Update already saved data state
                    setMappingValues(newData);
                    allMappingValues.current = newData;
                    setTotalValues(newData.length);
                    // Reset new values state
                    newMappingValues.current = [];
                    // Reset
                    changed.current.mappingValues = false;
                    return {
                        type: 'success',
                        message: __("Item saved successfully!", 'domain-mapping-system'),
                    };
                }).catch(e => {
                    debug && console.error(e);
                    return {
                        type: 'error',
                        message: __("Failed to create mapping values.", 'domain-mapping-system'),
                        info: `${host}${path ? '/' + path : ''}`,
                    };
                });
            });
        }).catch(e => {
            debug && console.error(e);
            return {
                type: 'error',
                message: __("Failed to create mapping.", 'domain-mapping-system'),
                info: `${host}${path ? '/' + path : ''}`,
            };
        });
    }

    /**
     * Update mapping data only (except values)
     *
     * @return {Promise<{type: string, message: string, info: string|undefined}>}
     */
    const updateMap = () => {
        if (changed.current.host || changed.current.path || changed.current.customHtml || changed.current.favicon) {
            const updateData = {};
            // Host
            if (changed.current.host) {
                updateData.host = host;
            }
            // Path
            if (changed.current.path) {
                updateData.path = path;
            }
            // Custom html
            if (changed.current.customHtml) {
                updateData.customHtml = customHtml;
            }
            // Favicon
            if (changed.current.favicon) {
                updateData.favicon = favicon;
            }
            // Update mapping
            return updateMapping(restUrl, restNonce, data.mapping.id, updateData).then(res => {
                // Reset to not update again
                changed.current.host = false;
                changed.current.path = false;
                changed.current.customHtml = false;
                changed.current.favicon = false;
                return {
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                };
            }).catch(e => {
                debug && console.error(e);
                return {
                    type: 'error',
                    message: __("Failed to update mapping.", 'domain-mapping-system'),
                    info: `${host}${path ? '/' + path : ''}`,
                };
            });
        } else {
            return new Promise((resolve) => {
                resolve({
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                });
            });
        }
    }

    /**
     * Update mapping meta
     *
     * @returns {Promise<unknown>|Promise<{type: string, message: string}>}
     */
    const updateMapMeta = () => {
        let changedItem = false;
        let updateData = [];
        if (changed.current.locale) {
            updateData.push({
                mapping_id: data.mapping.id,
                key: 'locale',
                value: selectedLocale.locale,
            });
            changedItem = true;
        }
        if (changed.current.customBody) {
            updateData.push({
                mapping_id: data.mapping.id,
                key: 'custom_body',
                value: customBody
            })
            changedItem = true;
        }
        if (changedItem) {
            return mappingMetaBatch(restUrl, restNonce, data.mapping.id, null, updateData, null).then(res => {
                changed.current.locale = false;
                return {
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                };
            })
        } else {
            return new Promise((resolve) => {
                resolve({
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                });
            });
        }
    }

    /**
     * Create mapping meta
     *
     * @param mappingId
     * @returns {Promise<unknown>|Promise<{type: string, message: string}>}
     */
    const createMapMeta = (mappingId) => {
        let changedItem = false;
        let createData = [];
        if (changed.current.locale) {
            createData.push({
                mapping_id: mappingId,
                key: 'locale',
                value: selectedLocale.locale,
            })
            changedItem = true;
        }
        if (changed.current.customBody) {
            createData.push({
                mapping_id: mappingId,
                key: 'custom_body',
                value: customBody
            })
            changedItem = true;
        }
        if (changedItem) {
            return mappingMetaBatch(restUrl, restNonce, mappingId, createData, null, null).then(res => {
                changed.current.locale = false;
                return {
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                };
            })
        } else {
            return new Promise((resolve) => {
                resolve({
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                });
            });
        }
    }

    /**
     * Set all mapping values
     *
     * @return {Promise<boolean>}
     */
    const setAllMappingValues = async () => {
        // Get all mapping values if didn't
        if (!allMappingValues.current.length) {
            try {
                const amv = await getMappingValues(restUrl, restNonce, data.mapping.id, 1, -1);
                allMappingValues.current = amv.items;
                setTotalValues(+amv._total);
            } catch (e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update mapping with values
     *
     * @return {Promise<{type: string, message: string, info: string|undefined}>}
     */
    const update = () => {
        return updateMap().then(async res => {
            return updateMapMeta().catch(metaError => {
                debug && console.error(metaError);
                return {
                    type: 'error',
                    message: __("Failed to update metadata.", 'domain-mapping-system'),
                    info: `${host}${path ? '/' + path : ''}`,
                };
            }).then(async res => {
                     // Get all mappings
                    const result = await setAllMappingValues();
                    if (!result) {
                        return new Promise((resolve) => {
                            resolve({
                                type: 'error',
                                message: __("Failed to update mapping values.", 'domain-mapping-system'),
                                info: `${host}${path ? '/' + path : ''}`,
                            });
                        });
                    }
                    // Get values that should be deleted
                    const mappingValuesToDelete = [];
                    for (const object of mappingValues) {
                        // If saved object (just for sure)
                        if (object.value.id) {
                            // If it doesn't exist in the new list then should be deleted
                            if (newMappingValues.current.findIndex(newValue => newValue.value === dataValue(object.value)) === -1) {
                                mappingValuesToDelete.push({id: object.value.id});
                            }
                        }
                    }
                    // Check values to not duplicate data
                    newMappingValues.current = newMappingValues.current.map(obj => {
                        if (obj.id) {
                            return obj;
                        }
                        // Check existence in all values
                        const ov = optionValue(obj);
                        const i = allMappingValues.current.findIndex(mv => ov === dataValue(mv.value));
                        if (i !== -1) {
                            return {
                                ...dataValueToSelectValue(allMappingValues.current[i]),
                                ...obj,
                            };
                        }
                        return obj;
                    });
                    return updateMappingValues(restUrl, restNonce, data.mapping.id, newMappingValues.current, mappingValuesToDelete).then(res2 => {
                        const newData = JSON.parse(JSON.stringify(mappingValues));
                        let totalsDiff = 0;
                        for (const methodData of res2) {
                            if (!methodData.data?.length) {
                                continue;
                            }
                            if (methodData.method === 'create') {
                                // Add new data
                                newData.push(...methodData.data);
                                allMappingValues.current.push(...methodData.data);
                                totalsDiff += methodData.data.length;
                            } else if (methodData.method === 'update') {
                                // Update existing data
                                for (const datum of methodData.data) {
                                    const i = newData.findIndex(obj => obj.value.id === datum.value.id);
                                    if (i !== -1) {
                                        newData[i] = datum;
                                    } else {
                                        // Add data that has been selected but already saved to values not yet loaded
                                        newData.push(datum);
                                    }
                                    // Update data in all mapping values
                                    const j = allMappingValues.current.findIndex(obj => obj.value.id === datum.value.id);
                                    if (j !== -1) {
                                        allMappingValues.current[j] = datum;
                                    }
                                }
                            }
                        }
                        // Remove deleted items
                        if (mappingValuesToDelete.length) {
                            for (const datum of mappingValuesToDelete) {
                                const i = newData.findIndex(obj => obj.value.id === datum.id);
                                if (i !== -1) {
                                    newData.splice(i, 1);
                                    totalsDiff--;
                                }
                                // Delete data from the all mapping values
                                const j = allMappingValues.current.findIndex(obj => obj.value.id === datum.id);
                                if (j !== -1) {
                                    allMappingValues.current.splice(j, 1);
                                }
                            }
                        }
                        // Update already saved data state
                        setMappingValues(newData);
                        setTotalValues(totalValues + totalsDiff);
                        // Reset new values state
                        newMappingValues.current = [];
                        // Reset
                        changed.current.mappingValues = false;
                        return {
                            type: 'success',
                            message: __("Item saved successfully!", 'domain-mapping-system'),
                        }
                    }).catch(e => {
                        debug && console.error(e);
                        return {
                            type: 'error',
                            message: __("Failed to update mapping values.", 'domain-mapping-system'),
                            info: `${host}${path ? '/' + path : ''}`,
                        };
                    });
            });
        }).catch(e => {
            debug && console.error(e);
            return {
                type: 'error',
                message: __("Something went wrong.", 'domain-mapping-system'),
                info: `${host}${path ? '/' + path : ''}`,
            };
        });
    }

    /**
     * On host change
     *
     * @param {Event} e Event
     */
    const hostChanged = (e) => {
        changed.current.host = true;
        setHost(e.target.value);
    }

    /**
     * On path change
     *
     * @param {Event} e Event
     */
    const pathChanged = (e) => {
        changed.current.path = true;
        setPath(e.target.value);
    }

    /**
     * On selected options change
     *
     * @param {object[]} selects
     * @param {boolean} justLoaded Just loading data or not
     */
    const mappingValuesChanged = (selects, justLoaded = false) => {
        newMappingValues.current = selects;
        !justLoaded && (changed.current.mappingValues = true);
    }

    /**
     * Check and keep correct data, mainly already saved data
     *
     * @param {object[]} selects Selects
     * @return {object[]}
     */
    const checkSelects = (selects) => {
        return selects.map(select => {
            if (!select.id) {
                const ov = optionValue(select);
                const i = mappingValues.findIndex(value => ov === dataValue(value.value));
                if (i !== -1) {
                    return dataValueToSelectValue(mappingValues[i]);
                }
            }
            return select;
        });
    }

    /**
     * Load more values
     *
     * @return {Promise<void>}
     */
    const getMoreValues = () => {
        return getMappingValues(restUrl, restNonce, data.mapping.id, valuesPaged + 1, valuesPerPage).then(res => {
            setTotalValues(+res._total);
            setMappingValues(prevState => [...prevState, ...res.items.filter(newObj => prevState.findIndex(obj => obj.value?.id === newObj.value?.id) === -1)]);
            setValuesPaged(valuesPaged + 1);
        }).catch(e => debug && console.error(e));
    }

    /**
     * On custom change
     *
     * @param {Event} e Event
     */
    const customHtmlChanged = (e) => {
        changed.current.customHtml = true;
        setCustomHtml(e.target.value);
    }

    /**
     * On custom body change
     *
     * @param e
     */
    const customBodyChanged = (e) => {
        changed.current.customBody = true;
        setCustomBody(e.target.value);
    }

    /**
     * On favicon change
     */
    const faviconChanged = () => {
        changed.current.favicon = true;
    }

    /**
     * On locale change
     *
     * @param newLocale
     */
    const localeChanged = (newLocale) => {
        changed.current.locale = true; // Mark locale as changed
        setSelectedLocale(newLocale); // Update selectedLocale state
    };
    return (
        <>
            <div className="dms-n-config-table dms-n-config-table-new">
                <button className={"dms-n-config-table-dropdown" + (open ? ' opened' : '')}
                        onClick={() => setOpen(!open)}>
                    <i></i>
                </button>
                <div className="dms-n-config-table-in">
                    <div className="dms-n-config-table-row first">
                        <div className="dms-n-config-table-column domain">
                            <div className={'dms-n-config-table-header' + (!isPremium ? ' free-version' : '')}>
                                <p>
                                    <span>{__("Enter Mapped Domain", 'domain-mapping-system')}</span>
                                </p>
                            </div>
                            <div className="dms-n-config-table-body">
                                <span className="dms-n-config-table-body-scheme">{window.location.protocol}//</span>
                                <input type="text" className="dms-n-config-table-input dms-host"
                                       placeholder="example.com" value={host} onChange={hostChanged}/>
                                <span className="slash">/</span>
                            </div>
                        </div>
                        <div className="dms-n-config-table-column subdirectory">
                            <div className={'dms-n-config-table-header' + (!isPremium ? ' free-version' : '')}>
                                <p>
                                    <span>{__("Enter Subdirectory (optional)", 'domain-mapping-system')}</span>
                                    {!isPremium && <a href={upgradeUrl}>{__('Upgrade', 'domain-mapping-system')}
                                        <span>&#8594;</span></a>}
                                </p>
                            </div>
                            <div className="dms-n-config-table-body">
                                <input type="text" className="dms-n-config-table-input dms-path"
                                       placeholder={__("Sub Directory", 'domain-mapping-system')} disabled={!isPremium}
                                       value={path} onChange={pathChanged}/>
                                <span className="slash">/</span>
                            </div>
                        </div>
                        <div className="dms-n-config-table-column content">
                            <div className={'dms-n-config-table-header' + (!isPremium ? ' free-version' : '')}>
                                <p>
                                    <span>{__("Select the Published Content to Map for this Domain.", 'domain-mapping-system')}</span>
                                    {!isPremium &&
                                        <span>{__("To map multiple published resources to a single domain, please.", 'domain-mapping-system')}</span>}
                                    {!isPremium && <a href={upgradeUrl}>{__('Upgrade', 'domain-mapping-system')}
                                        <span>&#8594;</span></a>}
                                </p>
                                {isPremium && <Tooltip/>}
                            </div>
                            <div className="dms-n-config-table-body dms-n-config-table-body-select">
                                {optionsLoaded && <SelectField selectedData={mappingValues}
                                                               defaultObjects={defaultObjects}
                                                               changed={mappingValuesChanged}
                                                               checkSelects={checkSelects}
                                                               totalValues={totalValues} updateTotals={setTotalValues}
                                                               getMoreValues={getMoreValues}
                                                               rendered={() => rendered(index)}
                                                               restUrl={restUrl} restNonce={restNonce}
                                                               isPremium={isPremium} debug={debug}/>}
                            </div>
                        </div>
                    </div>
                    <div className={'dms-n-config-table-row' + (!open ? ' closed' : '')}>
                        <div className={'dms-n-config-table-code-column'}>
                            <div className="dms-n-config-table-column code">
                                <div className="dms-n-config-table-header">
                                    <p>
                                        <span>{__("<head> per domain", 'domain-mapping-system')}</span>
                                        {!isPremium && <a href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')}
                                            <span>&#8594;</span></a>}
                                    </p>
                                </div>
                                <div className="dms-n-config-table-body">
                                    <input type="text" className="dms-n-config-table-input-code"
                                           placeholder={'</' + __("Code here", 'domain-mapping-system') + '>'}
                                           value={customHtml} onChange={customHtmlChanged} disabled={!isPremium}/>
                                </div>
                            </div>
                            <div className="dms-n-config-table-column code">
                                <div className="dms-n-config-table-header">
                                    <p>
                                        <span>{__("<body> per domain", 'domain-mapping-system')}</span>
                                        {!isPremium && <a href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')}
                                            <span>&#8594;</span></a>}
                                    </p>
                                </div>
                                <div className="dms-n-config-table-body">
                                    <input type="text" className="dms-n-config-table-input-code"
                                           placeholder={'</' + __("Code here", 'domain-mapping-system') + '>'}
                                           value={customBody} onChange={customBodyChanged} disabled={!isPremium}/>
                                </div>
                            </div>
                        </div>
                        <div className="dms-n-config-table-column favicon">
                            <div className="dms-n-config-table-header">
                                <p>
                                    <span>{__("Favicon per Domain", 'domain-mapping-system')}</span>
                                    {!isPremium && <a href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')}
                                        <span>&#8594;</span></a>}
                                </p>
                            </div>
                            <div className="dms-n-config-table-body">
                                <MediaUploader isPremium={isPremium} image={favicon} setImage={setFavicon}
                                               changed={faviconChanged}/>
                            </div>
                        </div>
                        {isMultilingual ?
                            (<div className="dms-n-config-table-column favicon">
                                <div className="dms-n-config-table-header">
                                    <p>
                                        <span>{__("Language per Domain", 'domain-mapping-system')}</span>
                                        {!isPremium && <a href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')}
                                            <span>&#8594;</span></a>}
                                    </p>
                                </div>
                                <div className="dms-n-config-table-body">
                                    <LanguageDropdown isPremium={isPremium} selectedLocale={selectedLocale}
                                                      languages={languages} changed={localeChanged}/>
                                </div>
                            </div>) : ''
                        }
                    </div>
                </div>
                {deleteRow && <button className="dms-n-config-table-delete" onClick={() => deleteEntry(data.uniqueKey)}>
                    <i></i>
                </button>}
            </div>
        </>
    );
});

export default Entry;