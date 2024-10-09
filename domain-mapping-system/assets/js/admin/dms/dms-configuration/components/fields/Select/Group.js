import {useState} from "react";
import {__} from "@wordpress/i18n";
import {components} from "react-select";
import Loading from "./Loading";

export default function Group({props, loadMore, debug}) {
    const [loading, setLoading] = useState(false);
    const [hasMore, setHasMore] = useState(props.data.pagination?.current_page < props.data.pagination?.total_pages);

    /**
     * Load more objects of the type
     */
    const loadMoreObjects = () => {
        setLoading(true);
        loadMore(props.data.name, props.data.pagination.current_page + 1).then(data => {
            setHasMore(data.hasMorePages);
            setLoading(false);
            return data.callback;
        }).then(f => typeof f === 'function' && f()).catch(e => {
            setLoading(false);
            debug && console.error(e);
        });
    }

    return <components.Group {...props}>
        {props.children}
        {hasMore && <div className="dms-n-config-table-react-select__option-loadmore">
            {loading ? <Loading/> : <button onClick={loadMoreObjects}>{__("Load more", 'domain-mapping-system')}</button>}
        </div>}
    </components.Group>
}