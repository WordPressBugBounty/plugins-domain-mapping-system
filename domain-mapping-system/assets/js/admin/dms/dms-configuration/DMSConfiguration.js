import {useEffect, useRef, useState} from 'react';
import Entry from "./components/Entry";
import Header from "./components/Header";
import Footer from "./components/Footer";
import {__, sprintf} from "@wordpress/i18n";
import {deleteMappings, fetchLanguages, getMappings, searchObject} from "../helpers/rest";
import Notice from "../_components/Notice";
import {makeUniqueKey} from "./helpers/helper";

export default function DMSConfiguration({isPremium, upgradeUrl, restUrl, permalinkOptions, restNonce, mapsPaged, mapsPerPage, valuesPerPage, isMultilingual, debug}) {
    const requirements = useRef(0);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [domainsMap, setDomainsMap] = useState([]);
    const [selectDefaultOptions, setSelectDefaultOptions] = useState([]);
    const [showDomains, setShowDomains] = useState(false);
    const [itemsCount, setItemsCount] = useState(0);
    const [totalItems, setTotalItems] = useState(0);
    const [paged, setPaged] = useState(mapsPaged || 1);
    const [notices, setNotices] = useState([]);
    const [entriesToBeDeleted, setEntriesToBeDeleted] = useState([]);
    const entriesRefs = useRef([]);
    const [languages, setLanguages] = useState([]);

    useEffect(() => {
        console.log(restUrl);
        if (restUrl.indexOf('?') !== -1) {
            setNotices([...notices, {
                type: 'error',
                message: sprintf(__("Plain %s permalinks %s are not supported yet.", 'domain-mapping-system'), `<a href="${permalinkOptions}">`, `</a>`),
            }])
            setLoading(false);
        } else {
            // Get mappings
            getEntries(paged);
            getSelectDefaultOptions();
            getLanguages();
        }

    }, []);

    /**
     * Get the languages
     */
    const getLanguages = () => {
        if (isMultilingual) {
            fetchLanguages(restUrl, restNonce).then(data => {
                setLanguages(data);
            }).catch(e => {
                debug && console.error(e);
                // Show error
                setNotices([...notices, {
                    type: 'error',
                    message: e.message,
                }]);
                // Hide loading
                setLoading(false);
            })
        }
    }

    /**
     * Get mapping entries
     *
     * @param {number} paged Paged num
     * @param {boolean} firstLoad Is first load
     */
    const getEntries = (paged, firstLoad = true) => {
        setLoading(true);
        setShowDomains(false);
        // Get mappings
        getMappings(restUrl, restNonce, paged, mapsPerPage).then(data => {
            // Pagination items
            setItemsCount(data.items.length);
            setTotalItems(parseInt(data._total));
            setPaged(paged);
            // Change/Add `paged` query param in the URL
            const url = new URL(window.location);
            if (paged > 1) {
                url.searchParams.set('paged', paged);
            } else {
                url.searchParams.delete('paged');
            }
            window.history.pushState({}, '', url);
            // Entries
            setDomainsMap(data.items.map(entry => ({
                ...entry,
                uniqueKey: makeUniqueKey()
            })));
            // Add a new entry if there are no entries yet
            if (!data.items.length) {
                addNewEntry();
            }
            // Show entriesState
            if (firstLoad) {
                if (++requirements.current === 2) {
                    setShowDomains(true);
                }
            } else {
                setShowDomains(true);
            }
        }).catch(e => {
            debug && console.error(e);
            // Show error
            setNotices([...notices, {
                type: 'error',
                message: e.message,
            }]);
            // Hide loading
            setLoading(false);
        });
    }

    /**
     * Get select default options
     */
    const getSelectDefaultOptions = () => {
        // Search objects
        searchObject(restUrl, restNonce, '').then(data => {
            setSelectDefaultOptions(data);
        }).catch(e => debug && console.error(e)).finally(() => {
            // Show entriesState
            if (++requirements.current === 2) {
                setShowDomains(true);
            }
        });
    }

    /**
     * Add new entry
     */
    const addNewEntry = () => {
        setDomainsMap([...domainsMap, {
            uniqueKey: makeUniqueKey(),
            mapping: {
                id: 0,
            }
        }]);
    }

    /**
     * Update entry data locally
     *
     * @param {object} data Domain map new data
     * @param {string} key Domain map key
     */
    const updateEntry = (data, key) => {
        setDomainsMap(prevState => prevState.map(entry => {
            if (entry.uniqueKey === key) {
                if (data.mapping) {
                    // Update map
                    entry.mapping = {
                        ...(entry.mapping ? entry.mapping : {}),
                        ...data.mapping,
                    };
                }
                if ('_values' in data) {
                    // Update values
                    if (!entry._values || !Array.isArray(entry._values.items)) {
                        entry._values = {
                            items: [],
                        };
                    }
                    // Update values
                    if (data._values.items?.length) {
                        entry._values.items.splice(0, entry._values.items.length, ...data._values.items);
                    } else if (entry._values.items.length) {
                        entry._values.items.splice(0, entry._values.items.length);
                    }
                }
            }
            return entry;
        }))
    }

    /**
     * Delete entry
     *
     * @param {string} key Entry unique key
     */
    const deleteEntry = (key) => {
        setDomainsMap(prevState => prevState.filter((entry, i) => {
            if (entry.uniqueKey === key) {
                setEntriesToBeDeleted([...entriesToBeDeleted, {
                    id: entry.mapping.id,
                    info: `${entry.host}${entry.path ? '/' + entry.path : ''}`
                }])
                return false;
            }
            return true;
        }));
    }

    /**
     * Hide loading if all mappings are rendered
     *
     * @param {number} i Mapping index
     */
    const entryRendered = (i) => {
        if (i === domainsMap.length - 1) {
            setLoading(false);
        }
    }

    /**
     *
     * @param {string[]} failedSaves Failed to create or update
     * @param {string[]} failedDeletes Failed to delete
     * @param {boolean} hasNoMappings If there ara an item(s) which has no mappings
     * @param {boolean} homepageNonPrimary If there are item(s) which has a homepage value that isn't primary
     */
    const savingEnds = (failedSaves, failedDeletes, {hasNoMappings, homepageNonPrimary}) => {
        const errors = [];
        // Saving failed
        if (failedSaves.length) {
            // Error saving: domain-1.com , domain-2.com, domain-3.com
            errors.push({
                type: 'error',
                message: __("Error saving:", 'domain-mapping-system') + ' ' + failedSaves.join(', '),
            });
        }
        // Deletion failed
        if (failedDeletes.length) {
            // Error deleting: domain-1.com , domain-2.com, domain-3.com
            errors.push({
                type: 'error',
                message: __("Error deleting:", 'domain-mapping-system') + ' ' + failedDeletes.join(', '),
            });
        }
        // Has no mappings
        if (hasNoMappings) {
            // Settings saved, but each domain requires at least one mapping.
            errors.push({
                type: 'error',
                message: domainsMap.length > 1 ? __("Each domain requires at least one mapping.", 'domain-mapping-system') : __("Domain requires at least one mapping.", 'domain-mapping-system'),
            });
        }
        // Homepage non-primary
        if (homepageNonPrimary) {
            // The home page should be the main one.
            errors.push({
                type: 'error',
                message: __("The home page should be the main one.", 'domain-mapping-system'),
            });
        }
        // Show notices
        if (!errors.length) {
            // Success
            setNotices([...notices, {
                type: 'success',
                message: __("Items saved successfully!", 'domain-mapping-system'),
            }]);
        } else {
            setNotices([...notices, ...errors]);
        }
        setSaving(false);
    }

    /**
     * Delete entry
     *
     * @return {Promise<boolean>}
     */
    const deleteEntries = () => {
        return new Promise((resolve) => {
            if (entriesToBeDeleted.length) {
                deleteMappings(restUrl, restNonce, entriesToBeDeleted.map(entry => ({
                    id: entry.id
                }))).then(res => {
                    // Reset list after delete
                    setEntriesToBeDeleted([]);
                    resolve(true);
                }).catch(e => {
                    debug && console.error(e);
                    resolve(false);
                });
            } else {
                resolve(true);
            }
        });
    }

    /**
     * Save
     */
    const save = () => {
        setSaving(true);
        const failedSaveDomains = [], failedDeleteDomains = [];
        let hasNoMappings = false, homepageNonPrimary = false;
        // Delete/Create/Update
        deleteEntries().then(res => {
            if (!res) {
                // TODO check if delete not done then show correct message
                res.info && failedDeleteDomains.push(res.info);
            }
            if (domainsMap.length) {
                domainsMap.map((entry, i) => {
                    if (entriesRefs.current[i]) {
                        // Create or update
                        entriesRefs.current[i].save().then(res => {
                            if (res.type !== 'success') {
                                // Has no mappings error message case
                                if (res.hasNoMappings) {
                                    hasNoMappings = true;
                                } else if (res.homepageNonPrimary) {
                                    homepageNonPrimary = true;
                                } else {
                                    failedSaveDomains.push(res.info);
                                }
                            }
                            // It was last item
                            if (i === domainsMap.length - 1) {
                                savingEnds(failedSaveDomains, failedDeleteDomains, {hasNoMappings, homepageNonPrimary});
                            }
                        }).catch(e => {
                            debug && console.error(e);
                            // It was last item
                            if (i === domainsMap.length - 1) {
                                savingEnds(failedSaveDomains, failedDeleteDomains, {hasNoMappings, homepageNonPrimary});
                            }
                        });
                    } else {
                        debug && console.error("The ref to the entry does not exist!");
                        // It was last item
                        if (i === domainsMap.length - 1) {
                            savingEnds(failedSaveDomains, failedDeleteDomains, {hasNoMappings, homepageNonPrimary});
                        }
                    }
                });
            } else {
                savingEnds(failedSaveDomains, failedDeleteDomains, {hasNoMappings, homepageNonPrimary});
            }
        });
    }

    /**
     * Dismiss notice
     *
     * @param {number} index Notice index
     */
    const dismissNotice = (index) => {
        setNotices(notices.filter((notice, i) => i !== index));
    }

    return (
        <>
            {loading && <div className="dms-n-loading-container">
                <div className="dms-n-loader"></div>
            </div>}
            <Header/>
            <div className="dms-n-config-in">
                <div className="dms-n-config-container dms-tab-container">
                    <h3 className="dms-n-row-header dms-n-config-header">{__('Domains', 'domain-mapping-system')}</h3>
                    {showDomains && domainsMap.map((entry, index) =>
                        <Entry key={entry.uniqueKey} ref={(el) => (entriesRefs.current[index] = el)} index={index} data={entry}
                               defaultObjects={selectDefaultOptions} updateEntry={updateEntry} deleteEntry={deleteEntry}
                               rendered={entryRendered} deleteRow={true} openByDefault={isPremium} isPremium={isPremium}
                               upgradeUrl={upgradeUrl} restUrl={restUrl} restNonce={restNonce}
                               valuesPerPage={valuesPerPage} languages={languages} isMultilingual={isMultilingual} debug={debug}/>)}
                </div>
            </div>
            <Footer addNewEntry={addNewEntry} paged={paged} itemsCount={itemsCount} totalItems={totalItems}
                    perPage={mapsPerPage} getEntries={(paged) => getEntries(paged, false)}/>
            <div className="dms-n-row-submit-wrapper">
                <div className="dms-n-row-submit">
                    <button className="dms-submit" onClick={save}
                            disabled={saving}>{__('Save', 'domain-mapping-system')}</button>
                    {saving && <div className="dms-n-loader"></div>}
                </div>
                {!!notices.length && notices.map((notice, index) => <Notice key={index} index={index}
                                                                            dismiss={dismissNotice} data={notice}/>)}
            </div>
        </>
    );
}
