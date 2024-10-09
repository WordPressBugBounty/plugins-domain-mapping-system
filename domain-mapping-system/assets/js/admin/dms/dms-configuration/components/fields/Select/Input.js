import {useState} from "react";
import {__} from "@wordpress/i18n";
import {components} from "react-select";
import Loading from "./Loading";

export default function Input({props, hasMore, getValues, toggleMenu, isPremium}) {
    if (props.isHidden) {
        return <components.Input {...props} />;
    }

    const [loading, setLoading] = useState(false);

    /**
     * Load more objects of the type
     */
    const loadMore = () => {
        setLoading(true);
        getValues().finally(() => {
            setLoading(false);
        });
    }

    return <>
        {isPremium && hasMore && <div className="dms-n-config-table-react-select__loadmore">
            {loading ? <Loading/> : <button onClick={loadMore}>{__("Load more", 'domain-mapping-system')}</button>}
        </div>}
        <div className="dms-n-config-table-react-select__search" onClick={toggleMenu}>
            <components.Input {...props}/>
        </div>
    </>
}