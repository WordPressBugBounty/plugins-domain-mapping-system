import {useEffect, useRef, useState, useImperativeHandle, forwardRef} from "react";
import {__} from "@wordpress/i18n";
import Tooltip from "../../_components/Tooltip";
import SelectField from "./fields/Select";
import MediaUploader from "./utilities/MediaUploader";
import {createMapping, createMappingValues, getMappingValues, updateMapping, updateMappingValues} from "../../helpers/rest";
import {dataValue} from "../helpers/helper";

const Entry = forwardRef(({index, data, defaultObjects, updateEntry, deleteEntry, deleteRow, rendered, openByDefault, isPremium, upgradeUrl, restUrl, restNonce, valuesPerPage, debug}, ref) => {
    const [open, setOpen] = useState(openByDefault);
    const [host, setHost] = useState('');
    const [path, setPath] = useState('');
    const [valuesPaged, setValuesPaged] = useState(1);
    const [totalValues, setTotalValues] = useState(0);
    const [mappingValues, setMappingValues] = useState([]);
    const [newMappingValues, setNewMappingValues] = useState([]);
    const [optionsLoaded, setOptionsLoaded] = useState(false);
    const [customHtml, setCustomHtml] = useState('');
    const [favicon, setFavicon] = useState({id: false});
    // Handel change of the entry
    const changed = useRef({
        host: false,
        path: false,
        mappingValues: false,
        customHtml: false,
        favicon: false,
    });

    useImperativeHandle(ref, () => ({
        save() {
            if (Object.values(changed.current).some(c => c)) {
                // Requires at least one option
                if (!newMappingValues.length) {
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
                    // const i = newMappingValues.findIndex(value => value.type === 'posts_homepage');
                    // if (i !== -1 && !newMappingValues[i].primary) {
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
        // Set mapping values
        if (data._values?.items?.length) {
            const values= isPremium ? data._values.items : data._values.items.filter(mv => mv.value.primary);
            setMappingValues(values);
            setTotalValues(isPremium ? +data._values._total : 1);
        }
        setOptionsLoaded(true);
    }, []);

    /**
     * Create mapping with values
     *
     * @return {Promise<{type: string, message: string, info: string|undefined}>}
     */
    const create = () => {
        return createMapping(restUrl, restNonce, {favicon, customHtml, host, path}).then(res => {
            // Update entry
            updateEntry(res, data.uniqueKey);
            // Create values
            return createMappingValues(restUrl, restNonce, res.mapping.id, newMappingValues).then(res2 => {
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
                setTotalValues(newData.length);
                // Reset new values state
                setNewMappingValues([]);
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
     * Update mapping with values
     *
     * @return {Promise<{type: string, message: string, info: string|undefined}>}
     */
    const update = () => {
        return updateMap().then(res => {
            if (changed.current.mappingValues) {
                // Get values that should be deleted
                const mappingValuesToDelete = [];
                for (const object of mappingValues) {
                    // If saved object (just for sure)
                    if (object.value.id) {
                        // If it doesn't exist in the new list then should be deleted
                        if (newMappingValues.findIndex(newValue => newValue.value === dataValue(object.value)) === -1) {
                            mappingValuesToDelete.push({id: object.value.id});
                        }
                    }
                }
                return updateMappingValues(restUrl, restNonce, data.mapping.id, newMappingValues, mappingValuesToDelete).then(res2 => {
                    const newData = JSON.parse(JSON.stringify(mappingValues));
                    let totalsDiff = 0;
                    for (const methodData of res2) {
                        if (!methodData.data?.length) {
                            continue;
                        }
                        if (methodData.method === 'create') {
                            // Add new data
                            newData.push(...methodData.data);
                            totalsDiff += methodData.data.length;
                        } else if (methodData.method === 'update') {
                            // Update existing data
                            for (const datum of methodData.data) {
                                const i = newData.findIndex(obj => obj.value.id === datum.value.id);
                                if (i !== -1) {
                                    newData[i] = datum;
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
                        }
                    }
                    // Update already saved data state
                    setMappingValues(newData);
                    setTotalValues(totalValues + totalsDiff);
                    // Reset new values state
                    setNewMappingValues([]);
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
            } else {
                return {
                    type: 'success',
                    message: __("Item saved successfully!", 'domain-mapping-system'),
                }
            }
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
        setNewMappingValues(selects);
        !justLoaded && (changed.current.mappingValues = true);
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
     * On favicon change
     */
    const faviconChanged = () => {
        changed.current.favicon = true;
    }

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
                            <div className="dms-n-config-table-body">
                                {optionsLoaded && <SelectField selectedData={mappingValues}
                                                               defaultObjects={defaultObjects}
                                                               changed={mappingValuesChanged}
                                                               totalValues={totalValues} updateTotals={setTotalValues}
                                                               getMoreValues={getMoreValues}
                                                               rendered={() => rendered(index)}
                                                               restUrl={restUrl} restNonce={restNonce}
                                                               isPremium={isPremium} debug={debug}/>}
                            </div>
                        </div>
                    </div>
                    <div className={'dms-n-config-table-row' + (!open ? ' closed' : '')}>
                        <div className="dms-n-config-table-column code">
                            <div className="dms-n-config-table-header">
                                <p>
                                    <span>{__("Custom HTML Code", 'domain-mapping-system')}</span>
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