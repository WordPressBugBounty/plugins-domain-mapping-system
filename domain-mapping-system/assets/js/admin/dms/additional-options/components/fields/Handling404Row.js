import {__, sprintf} from "@wordpress/i18n";

export default function Handling404Row({slug, slugSc, value, updateValue, selectValue, isPremium, upgradeUrl}) {
    /**
     * On checkbox change
     */
    const changed = () => {
        updateValue(prevState => ({
            ...prevState,
            [slug]: {
                ...prevState[slug],
                value: !value ? 'on' : false,
                changed: true,
            },
        }));
    }

    /**
     * Update select value
     *
     * @param {Event} e Event
     */
    const selectValueChanged = (e) => {
        updateValue(prevState => ({
            ...prevState,
            [slugSc]: {
                ...prevState[slug],
                value: e.target.value,
                changed: true,
            },
        }));
    }

    return <li>
        <div className="dms-n-additional-accordion-li">
            <div className="dms-n-additional-accordion-checkbox">
                <input className="checkbox" type="checkbox" disabled={!isPremium} checked={isPremium && value === 'on'}
                       onChange={changed}/>
            </div>
            <div className="dms-n-additional-accordion-content">
                <span className="label">
                    <span
                        dangerouslySetInnerHTML={{__html: sprintf(__("%s404 Handling%s - For unmapped URLs:", 'domain-mapping-system'), '<strong>', '</strong>')}}></span>
                    &nbsp;<select disabled={!isPremium} value={selectValue} onChange={selectValueChanged}>
                        <option value="1">{__("Throw 404 error", 'domain-mapping-system')}</option>
                        <option value="2">{__("Redirect to microsite homepage", 'domain-mapping-system')}</option>
                    </select>.
                    <span
                        dangerouslySetInnerHTML={{__html: "&nbsp;" + sprintf(__("Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" href="https://docs.domainmappingsystem.com/features/404-handling/" target="_blank" >', '</a>')}}></span>
                    {!isPremium && <>
                        &nbsp;
                        <a className="upgrade" href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')} &#8594;</a>
                    </>}
                </span>
            </div>
        </div>
    </li>
}