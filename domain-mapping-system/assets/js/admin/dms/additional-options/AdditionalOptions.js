import {useState} from "react";
import Settings from "./components/Settings";

export default function AdditionalOptions({isPremium, upgradeUrl, restUrl, restNonce, siteUrl, isMultilingual, debug}) {
    const [loading, setLoading] = useState(true);

    return <>
        {loading && <div className="dms-n-loading-container">
            <div className="dms-n-loader"></div>
        </div>}
        <Settings isPremium={isPremium} upgradeUrl={upgradeUrl} restUrl={restUrl} restNonce={restNonce}
                  loading={setLoading} siteUrl={siteUrl} isMultilingual={isMultilingual} debug={debug}/>
    </>
}