import {__} from "@wordpress/i18n";

export default function Checkbox({slug, title, value, changed, isArchive = false}) {
    return <label className={`dms-n-post-types-label${value === 'on' ? ' checked' : ''}`} htmlFor={slug}>
        <input id={slug} className="dms-n-post-types-checkbox" type="checkbox"
               checked={value === 'on'} onChange={() => changed(slug)}/>
        <span>{title}{isArchive && <>:&nbsp;<strong>{__("Archive", 'domain-mapping-system')}</strong></>}</span>
    </label>
}