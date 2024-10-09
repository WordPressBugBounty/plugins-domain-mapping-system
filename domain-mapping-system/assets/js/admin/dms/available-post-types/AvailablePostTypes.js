import {useEffect, useState} from "react";
import {__, sprintf} from "@wordpress/i18n";
import Checkbox from "./components/fields/Checkbox";
import Notice from "../_components/Notice";
import {getSettings, updateSetting} from "../helpers/rest";
import {settingsData} from "./data/settings";

export default function AvailablePostTypes({availableObjects, isPremium, upgradeUrl, restUrl, restNonce, debug}) {
    const [saving, setSaving] = useState(true);
    const [loading, setLoading] = useState(true);
    const [showSettings, setShowSettings] = useState(false);
    const [notices, setNotices] = useState([]);
    const [settings, setSettings] = useState(settingsData);
    const [contentTypes, setContentTypes] = useState([]);

    useEffect(() => {
        setLoading(true);
        // Available custom objects
        if (availableObjects?.length) {
            availableObjects.forEach(type => {
                // Object key
                settings[`dms_use_${type.name}`] = {
                    value: false,
                    changed: false,
                };
                // Archive key
                if (type.has_archive) {
                    settings[`dms_use_${type.name}_archive`] = {
                        value: false,
                        changed: false,
                    };
                }
                // Object taxonomies
                if (type.taxonomies?.length) {
                    type.taxonomies.forEach(taxonomy => {
                        settings[`dms_use_cat_${type.name}_${taxonomy.name}`] = {
                            value: false,
                            changed: false,
                        };
                    });
                }
            });
            setContentTypes(availableObjects);
        }
        const keys = Object.keys(settings);
        // Get settings
        getSettings(restUrl, restNonce, keys).then(res => {
            for (const setting of res) {
                settings[setting.key].value = setting.value
            }
            setSettings(settings);
        }).catch(e => {
            debug && console.error(e);
            // Show error message
            setNotices([{
                type: 'error',
                message: __("Failed to get settings data.", 'domain-mapping-system'),
            }]);
        }).finally(() => {
            setShowSettings(true);
            setLoading(false);
        });
    }, []);

    /**
     * Save
     */
    const save = () => {
        // Get changed setting data only
        const settingsToUpdate = [];
        for (const key in settings) {
            if (settings[key].changed) {
                settingsToUpdate.push({
                    key,
                    value: settings[key].value,
                });
            }
        }
        if (!settingsToUpdate.length) {
            return;
        }
        setSaving(true);
        let hasError = false;
        const updatedKeys = [];
        // Loop on settings to create/update
        settingsToUpdate.forEach((setting, index) => {
            updateSetting(restUrl, restNonce, setting).then(res => {
                updatedKeys.push(setting.key);
            }).catch(e => {
                hasError = true;
                debug && console.error(e);
                // Show error message
                setNotices([{
                    type: 'error',
                    message: sprintf(__("Failed to save %s%s%s setting data.", 'domain-mapping-system'), '<strong>', setting.key, '</strong>'),
                }]);
            }).finally(() => {
                if (index === settingsToUpdate.length - 1) {
                    // Update state, changed => false
                    updatedKeys.forEach(key => settings[key].changed = false);
                    setSettings(settings);
                    // Show success message if hase no error
                    if (!hasError) {
                        setNotices([{
                            type: 'success',
                            message: __("Items saved successfully!", 'domain-mapping-system'),
                        }]);
                    }
                    // Last setting
                    setSaving(false);
                }
            });
        });
    }

    /**
     * Checkbox changed
     *
     * @param {string} key Setting key
     */
    const checkboxChanged = (key) => {
        setSettings(prevState => ({
            ...prevState,
            [key]: {
                ...(settings[key] || {}),
                value: !settings[key]?.value ? 'on' : false,
                changed: true,
            },
        }));
    }

    /**
     * Dismiss notice
     *
     * @param {number} index Notice index
     */
    const dismissNotice = (index) => {
        setNotices(notices.filter((notice, i) => i !== index));
    }

    /**
     * Render content types content
     *
     * @return {JSX.Element}
     */
    const renderContentTypesContent = () => {
        if (contentTypes?.length) {
            return contentTypes.map(type => {
                const typeContents = [];
                typeContents.push(renderContentTypeContent(type));
                typeContents.push(renderContentTypeArchiveContent(type));
                typeContents.push(renderContentTypeTaxonomiesContent(type));
                return typeContents;
            })
        }
        return '';
    }

    /**
     * Render content type content
     *
     * @param {object} type Type
     * @return {JSX.Element}
     */
    const renderContentTypeContent = (type) => {
        const settingKey = `dms_use_${type.name}`;
        return <Checkbox key={settingKey} slug={settingKey} title={type.label}
                         value={settings[settingKey]?.value} changed={checkboxChanged}/>;
    }

    /**
     * Render content type archive content
     *
     * @param {object} type Type
     * @return {JSX.Element|string}
     */
    const renderContentTypeArchiveContent = (type) => {
        if (type.has_archive) {
            const settingKey = `dms_use_${type.name}_archive`;
            return <Checkbox key={settingKey} slug={settingKey} title={type.label}
                             value={settings[settingKey]?.value} changed={checkboxChanged}
                             isArchive={true}/>
        }
        return '';
    }

    /**
     * Render content type taxonomies content
     *
     * @param {object} type Type
     * @return {JSX.Element}
     */
    const renderContentTypeTaxonomiesContent = (type) => {
        if (isPremium && type.taxonomies?.length) {
            return type.taxonomies.map(taxonomy => renderContentTypeTaxonomyContent(type, taxonomy));
        }
        return '';
    }

    /**
     * Render content type taxonomy content
     *
     * @param {object} type Type
     * @param {object} taxonomy Taxonomy
     * @return {JSX.Element}
     */
    const renderContentTypeTaxonomyContent = (type, taxonomy) => {
        let settingKey = `dms_use_cat_${type.name}_${taxonomy.name}`;
        // Specific case
        if (settingKey === 'dms_use_cat_post_category') {
            settingKey = 'dms_use_categories';
        }
        return <Checkbox key={settingKey} slug={settingKey} title={`${type.label}: ${taxonomy.label}`}
                         value={settings[settingKey]?.value} changed={checkboxChanged}/>
    }

    return <>
        {loading && <div className="dms-n-loading-container">
            <div className="dms-n-loader"></div>
        </div>}
        <h3 className="dms-n-row-header">{__("Available Post Types", 'domain-mapping-system')}</h3>
        <p className="dms-n-row-subheader">{__("Select the Post Types or Custom Taxonomies that should be available for Domain Mapping System.", 'domain-mapping-system')}</p>
        <div className="dms-n-post-types-in">
            {showSettings && <div className="dms-n-post-types-container">
                {renderContentTypesContent()}
            </div>}
            <div className="dms-n-row-submit-wrapper">
                <div className="dms-n-row-submit">
                    <button className="dms-submit" onClick={save}
                            disabled={!showSettings}>{__("Save", 'domain-mapping-system')}</button>
                    {saving && <div className="dms-n-loader"></div>}
                </div>
                {!!notices.length && notices.map((notice, index) => <Notice key={index} index={index}
                                                                            dismiss={dismissNotice} data={notice}/>)}
            </div>
        </div>
    </>
}