import {useState} from "react";
import Settings from "./components/Settings";
import {__} from "@wordpress/i18n";

export default function AdditionalOptions({isPremium, upgradeUrl, restUrl, restNonce, debug}) {
    const [loading, setLoading] = useState(true);
    const [open, setOpen] = useState(true);

    /**
     * Toggle accordion
     */
    const toggleAccordion = () => {
        setOpen(!open);
    }

    return <>
        {loading && <div className="dms-n-loading-container">
            <div className="dms-n-loader"></div>
        </div>}
        <div className={"dms-n-additional-accordion" + (open ? ' opened' : '')}>
            <div className="dms-n-additional-accordion-header" onClick={toggleAccordion}>
                <h3>
                    <span>{__("Additional Options", 'domain-mapping-system')}</span>
                </h3>
                <i></i>
            </div>
            <Settings isPremium={isPremium} upgradeUrl={upgradeUrl} restUrl={restUrl} restNonce={restNonce}
                      loading={setLoading} debug={debug}/>
        </div>
    </>
}