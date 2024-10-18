import {useState} from "react";
import {__} from "@wordpress/i18n";
import Loading from "./Loading";

export default function LoadMoreValues({hasMore, getValues}) {
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

    return hasMore && <div className="dms-n-config-table-react-select__loadmore">
        {loading ? <Loading/> : <button onClick={loadMore}>{__("Load more", 'domain-mapping-system')}</button>}
    </div>
}