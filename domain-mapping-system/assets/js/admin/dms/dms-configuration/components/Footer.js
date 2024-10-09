import {useEffect, useState} from 'react';
import {__} from "@wordpress/i18n";

export default function Footer({addNewEntry, paged, itemsCount, totalItems, perPage, getEntries}) {
    const [numItems, setNumItems] = useState('');
    const [lastPage, setTotalPages] = useState(1);
    const [prevPageDisabled, setPrevPageDisabled] = useState(true);
    const [nextPageDisabled, setNextPageDisabled] = useState(false);

    useEffect(() => {
        // Number of items
        setNumItems(totalItems + ' ' + (totalItems > 1 ? __('items', 'domain-mapping-system') : __('item', 'domain-mapping-system')));
        // Last page
        const lastPage = Math.ceil(totalItems / perPage);
        setTotalPages(lastPage);
        // Current page
        const currentPage = +paged || 1;
        setPrevPageDisabled(currentPage < 2);
        setNextPageDisabled(currentPage === lastPage);
    }, [paged, totalItems, perPage]);

    /**
     * Move on pages
     *
     * @param {first|prev|next|last} to Page description
     */
    const goToPage = (to) => {
        switch (to) {
            case 'first':
                getEntries(1);
                break;
            case 'prev':
                getEntries(+paged - 1);
                break;
            case 'next':
                getEntries(+paged + 1);
                break;
            case 'last':
                getEntries(lastPage);
                break;
        }
    }

    return (
        <div className="dms-n-row-footer">
            <div className="dms-n-row-add">
                <button className="dms-add-row"
                        onClick={addNewEntry}>{__("+ Add Domain Map Entry", 'domain-mapping-system')}</button>
            </div>
            <div className="dms-n-mappings-pagination">
                <div className="displaying-num">{numItems}</div>
                {lastPage > 1 && <div className="pagination-links">
                    <button className="first-page button" onClick={() => !prevPageDisabled && goToPage('first')}
                            disabled={prevPageDisabled}>
                        <span className="screen-reader-text">{__('First page', 'domain-mapping-system')}</span>
                        <span aria-hidden="true">«</span>
                    </button>
                    <button className="prev-page button" onClick={() => !prevPageDisabled && goToPage('prev')}
                            disabled={prevPageDisabled}>
                        <span className="screen-reader-text">{__('Previous page', 'domain-mapping-system')}</span>
                        <span aria-hidden="true">‹</span>
                    </button>
                    <span className="screen-reader-text">{__('Current Page', 'domain-mapping-system')}</span>
                    <span className="paging-input">
                        <span className="tablenav-paging-text">{paged} {__('of', 'domain-mapping-system')} <span
                            className="total-pages">{lastPage}</span></span>
                    </span>
                    <button className="next-page button" onClick={() => !nextPageDisabled && goToPage('next')}
                            disabled={nextPageDisabled}>
                        <span className="screen-reader-text">{__('Next page', 'domain-mapping-system')}</span>
                        <span aria-hidden="true">›</span>
                    </button>
                    <button className="last-page button" onClick={() => !nextPageDisabled && goToPage('last')}
                            disabled={nextPageDisabled}>
                        <span className="screen-reader-text">{__('Last page', 'domain-mapping-system')}</span>
                        <span aria-hidden="true">»</span>
                    </button>
                </div>}
            </div>
        </div>
    );
}