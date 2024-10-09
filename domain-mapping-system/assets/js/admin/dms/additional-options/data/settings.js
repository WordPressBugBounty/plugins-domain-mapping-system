import {__} from "@wordpress/i18n";

export const settingsData = {
    dms_global_mapping: {
        title: __("Global Domain Mapping", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_main_mapping: {
        title: __("Selected domains", 'domain-mapping-system'),
        value: [],
        changed: false,
    },
    dms_archive_global_mapping: {
        title: __("Global Archive Mapping", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_woo_shop_global_mapping: {
        title: __("Global WooCommerce Product Mapping", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_global_parent_page_mapping: {
        title: __("Global Parent Page Mapping", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_remove_parent_page_slug_from_child: {
        title: __("Parent Page Slugs", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_force_site_visitors: {
        title: __("Redirection", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_rewrite_urls_on_mapped_page: {
        title: __("URL Rewriting", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_rewrite_urls_on_mapped_page_sc: {
        title: __("Rewrite mapped domain type"),
        value: '1',
        changed: false,
    },
    dms_unmapped_pages_handling: {
        title: __("404 Handling", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_unmapped_pages_handling_sc: {
        title: __("Unmapped URLs handling type", 'domain-mapping-system'),
        value: '1',
        changed: false,
    },
    dms_seo_options_per_domain: {
        title: __("Duplicate Yoast SEO Options", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_seo_sitemap_per_domain: {
        title: __("Yoast Sitemap per Domain", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
    dms_delete_upon_uninstall: {
        title: __("Data Removal", 'domain-mapping-system'),
        value: false,
        changed: false,
    },
}