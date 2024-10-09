import {__} from "@wordpress/i18n";

export default function CheckboxRow({slug, title, value, updateValue, description, isPremium, upgradeUrl}) {
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

    return <li>
        <div className="dms-n-additional-accordion-li">
            <div className="dms-n-additional-accordion-checkbox">
                <input className="checkbox" type="checkbox" disabled={!isPremium}
                       checked={isPremium && value === 'on'} onChange={changed}/>
            </div>
            <div className="dms-n-additional-accordion-content">
                <span className="label">
                    <strong>{title}</strong> - <span dangerouslySetInnerHTML={{__html: description}}></span>
                </span>
                {!isPremium && <>
                    &nbsp;
                    <a className="upgrade" href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')} &#8594;</a>
                </>}
            </div>
        </div>
    </li>
}