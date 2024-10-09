import {__, sprintf} from "@wordpress/i18n";

export default function Header() {
    return <>
        <h3 className="dms-n-row-header">{__('Domain Mapping System Configuration', 'domain-mapping-system')}</h3>
        <p className="dms-n-row-subheader">
                <span
                    className="dms-n-row-subheader-important">{__('Important!', 'domain-mapping-system')}</span>
            <span
                dangerouslySetInnerHTML={{__html: sprintf(__("This plugin requires configuration with your DNS host and on your server (cPanel, etc). Please see %sour documentation%s for configuration requirements.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" href="https://docs.domainmappingsystem.com" target="_blank">', '</a>')}}></span>
        </p>
    </>;
}