import {useEffect, useState} from "react";
import {__, sprintf} from "@wordpress/i18n";
import CheckboxRow from "./fields/CheckboxRow";
import GlobalDomainMappingRow from "./fields/GlobalDomainMappingRow";
import UrlRewritingRow from "./fields/UrlRewritingRow";
import Handling404Row from "./fields/Handling404Row";
import Notice from "../../_components/Notice";
import {getSettings, updateSetting} from "../../helpers/rest";
import {settingsData} from "../data/settings";
import SubdomainAuthenticationRow from "./fields/SubdomainAuthentication";

export default function Settings({isPremium, upgradeUrl, restUrl, restNonce, loading, siteUrl, debug}) {
    const [saving, setSaving] = useState(false);
    const [showSettings, setShowSettings] = useState(false);
    const [notices, setNotices] = useState([]);
    const [settings, setSettings] = useState(settingsData);

    useEffect(() => {
        loading(true);
        const keys = Object.keys(settings);
        // Get settings
        getSettings(restUrl, restNonce, keys).then(res => {
            for (const setting of res) {
                if (setting.key === 'dms_main_mapping' || setting.key === 'dms_subdomain_authentication_mappings') {
                    settings[setting.key].value = Array.isArray(setting.value) ? setting.value : [setting.value];
                } else {
                    settings[setting.key].value = setting.value;
                }
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
            // Last setting
            setShowSettings(true);
            loading(false);
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
                    message: sprintf(__("Failed to save %s%s%s setting data.", 'domain-mapping-system'), '<strong>', settings[setting.key].title, '</strong>'),
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
     * Dismiss notice
     *
     * @param {number} index Notice index
     */
    const dismissNotice = (index) => {
        setNotices(notices.filter((notice, i) => i !== index));
    }

    return <div className="dms-n-additional-accordion-body">
        {showSettings && <ul>
            <GlobalDomainMappingRow slug="dms_global_mapping" slugMaps="dms_main_mapping"
                                    value={settings.dms_global_mapping.value}
                                    selectValue={settings.dms_main_mapping.value}
                                    updateValue={setSettings} restUrl={restUrl} restNonce={restNonce}
                                    isPremium={isPremium} upgradeUrl={upgradeUrl}
                                    loading={loading} debug={debug}/>
            <CheckboxRow key="archive_global_mapping" slug="dms_archive_global_mapping"
                         title={__("Global Archive Mapping", 'domain-mapping-system')}
                         value={settings.dms_archive_global_mapping.value} updateValue={setSettings}
                         description={sprintf(__("All posts within an archive or category automatically map to the specified domain (archive mappings override Global Domain Mapping). Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/global-archive-category-mapping">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="woo_shop_global_mapping" slug="dms_woo_shop_global_mapping"
                         title={__("Global WooCommerce Product Mapping", 'domain-mapping-system')}
                         value={settings.dms_woo_shop_global_mapping.value} updateValue={setSettings}
                         description={sprintf(__("When you map a domain to the Shop page, all products on your site will be available through that domain. Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/global-product-mapping-for-woocommerce">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="global_parent_page_mapping" slug="dms_global_parent_page_mapping"
                         title={__("Global Parent Page Mapping", 'domain-mapping-system')}
                         value={settings.dms_global_parent_page_mapping.value} updateValue={setSettings}
                         description={sprintf(__("Automatically map all pages attached to a Parent Page.  Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/global-parent-page-mapping">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="remove_parent_page_slug_from_child" slug="dms_remove_parent_page_slug_from_child"
                         title={__("Parent Page Slugs", 'domain-mapping-system')}
                         value={settings.dms_remove_parent_page_slug_from_child.value} updateValue={setSettings}
                         description={sprintf(__("Remove Parent Page slugs from mapped Child Page URLs. Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/global-parent-page-mapping">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="force_site_visitors" slug="dms_force_site_visitors"
                         title={__("Redirection", 'domain-mapping-system')}
                         value={settings.dms_force_site_visitors.value} updateValue={setSettings}
                         description={sprintf(__("Force site visitors to see only mapped domains of a page (e.g. - disallow visitors to see the primary site domain version of a page). Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/redirect-site-visitors-to-mapped-domains">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <UrlRewritingRow slug="dms_rewrite_urls_on_mapped_page" slugSc="dms_rewrite_urls_on_mapped_page_sc"
                             value={settings.dms_rewrite_urls_on_mapped_page.value}
                             selectValue={settings.dms_rewrite_urls_on_mapped_page_sc.value} updateValue={setSettings}
                             isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <Handling404Row slug="dms_unmapped_pages_handling" slugSc="dms_unmapped_pages_handling_sc"
                            value={settings.dms_unmapped_pages_handling.value}
                            selectValue={settings.dms_unmapped_pages_handling_sc.value} updateValue={setSettings}
                            isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <SubdomainAuthenticationRow slug="dms_subdomain_authentication" slugMaps="dms_subdomain_authentication_mappings"
                                        value={settings.dms_subdomain_authentication.value}
                                        selectValue={settings.dms_subdomain_authentication_mappings.value}
                                        updateValue={setSettings} restUrl={restUrl} restNonce={restNonce}
                                        isPremium={isPremium} upgradeUrl={upgradeUrl}
                                        loading={loading} siteUrl={siteUrl} debug={debug}/>
            <li className="dms-n-setting-title">
                <div className="dms-n-additional-accordion-li">
                    <strong>{__("Yoast SEO", 'domain-mapping-system')}</strong>
                </div>
            </li>
            <CheckboxRow key="seo_options_per_domain" slug="dms_seo_options_per_domain"
                         title={__("Duplicate Yoast SEO Options", 'domain-mapping-system')}
                         value={settings.dms_seo_options_per_domain.value} updateValue={setSettings}
                         description={sprintf(__("Each mapped page will have duplicated Yoast SEO options for each mapped domain tied to it.  Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/integrations-and-compatibility/wordpress-plugins/yoast-seo">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="seo_sitemap_per_domain" slug="dms_seo_sitemap_per_domain"
                         title={__("Yoast Sitemap per Domain", 'domain-mapping-system')}
                         value={settings.dms_seo_sitemap_per_domain.value} updateValue={setSettings}
                         description={sprintf(__("Dynamically generate a unique sitemap per domain. Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/integrations-and-compatibility/wordpress-plugins/yoast-seo#sitemap-per-domain">', '</a>')}
                         isPremium={isPremium} upgradeUrl={upgradeUrl}/>
            <CheckboxRow key="delete_upon_uninstall" slug="dms_delete_upon_uninstall"
                         title={__("Data Removal", 'domain-mapping-system')}
                         value={settings.dms_delete_upon_uninstall.value} updateValue={setSettings}
                         description={__("Delete plugin, data, and settings (full removal) when uninstalling.", 'domain-mapping-system') + ' ' + sprintf(__("%sWarning:%s This action is irreversible.", 'domain-mapping-system'), '<strong>', '</strong>')}
                         isPremium={true} upgradeUrl={upgradeUrl}/>
        </ul>}
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
}