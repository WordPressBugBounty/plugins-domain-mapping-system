import {__, isRTL} from "@wordpress/i18n";
import AsyncSelect from "react-select/async";
import {useEffect, useRef, useState} from "react";
import MultiValueLabel from "./Select/MultiValueLabel"
import SingleValue from "./Select/SingleValue"
import LoadingIndicator from "./Select/Loading"
import Group from "./Select/Group"
import {loadMoreObjects, searchObject} from "../../../helpers/rest";
import {dataValue, dataValueToSelectValue, debounce, optionValue, parseSearchedDataToGroupOptions, parseSearchedDataToOption} from "../../helpers/helper";
import LoadMoreValues from "./Select/LoadMoreValues";

export default function Select({selectedData, defaultObjects, changed, checkSelects, totalValues, updateTotals, getMoreValues, rendered, restUrl, restNonce, isPremium, debug}) {
    const isRtl = isRTL();
    const [hasMoreValues, setHasMoreValues] = useState(false);
    const [defaultValues, setDefaultValues] = useState([]);
    const [options, setOptions] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectKey, setSelectKey] = useState(0);
    const selectRef = useRef(null);
    const menuOpened = useRef(false);

    useEffect(() => {
        if (Array.isArray(defaultObjects)) {
            setOptions(parseSearchedDataToGroupOptions(defaultObjects, debug));
            rendered();
        }
    }, [defaultObjects]);

    useEffect(() => {
        // Update saved data (to show globe icon automatically)
        const newDefaultValuesState = checkSelects([
            ...selectedData.map(dataValueToSelectValue),
            ...defaultValues.filter(obj => selectedData.findIndex(item => optionValue(obj) === dataValue(item.value)) === -1),
        ]);
        setDefaultValues(newDefaultValuesState);
        changed(newDefaultValuesState, true);
        setSelectKey((selectKey + 1) % 2);
    }, [selectedData]);

    useEffect(() => {
        setHasMoreValues(isPremium && totalValues > defaultValues.filter(obj => obj.id).length);
    }, [isPremium, totalValues, defaultValues]);

    /**
     * Make an item primary
     *
     * @param {string|number} value Option value
     */
    const makePrimary = (value) => {
        const selects = checkSelects(defaultValues.map(data => {
            data.primary = data.value === value;
            return data;
        }));
        setDefaultValues(selects);
        changed(selects);
        // To fix the stuck issue when clicking on the home icon
        setTimeout(() => selectRef.current?.focus(), 10);
    }

    /**
     * Get options
     *
     * @param {string} inputValue Search term
     * @param {function} callback Resolver callback
     */
    const getOptions = (inputValue, callback) => {
        setSearchTerm(inputValue);
        // Search objects
        return searchObject(restUrl, restNonce, inputValue).then(data => {
            const foundOptions = parseSearchedDataToGroupOptions(data, debug);
            setOptions(foundOptions);
            callback(foundOptions);
        }).catch(e => debug && console.error(e)).finally(() => {
            // To not lose focus
            setTimeout(() => selectRef.current?.focus(), 10);
        });
    };

    /**
     * Load options
     *
     * @type {(function(...[*]): void)|*}
     */
    const loadOptions = React.useCallback(debounce(getOptions, 350), []);

    /**
     * Load more
     *
     * @param {string} groupName Objects group name
     * @param {number} page Page
     * @return {Promise<void>}
     */
    const loadMore = (groupName, page) => {
        return loadMoreObjects(restUrl, restNonce, groupName, searchTerm, page).then(data => {
            // Check new data availability
            if (!data?.objects?.length) {
                return false;
            }
            // Update options of the group by name
            const newOptions = options.map(group => {
                if (group.name === groupName) {
                    return {
                        ...group,
                        pagination: data._pagination,
                        key: `${groupName}:${data._pagination?.current_page}:${data._pagination?.per_page}`,
                        options: [...group.options, ...data.objects.map(item => parseSearchedDataToOption(item))]
                    }
                }
                return group;
            });
            setOptions(newOptions);
            // Check for more pages
            return {
                hasMorePages: data._pagination?.total_pages > page,
                callback: () => setTimeout(() => {
                    selectRef.current?.focus();
                    selectRef.current?.openMenu('first');
                }, 10),
            };
        });
    }

    /**
     * On selects change
     *
     * @param {array} selects Selects
     * @param {object} select Selected item
     */
    const onSelectsChange = (selects, select) => {
        if (!Array.isArray(selects)) {
            selects = [selects];
        }
        if (selects.length) {
            // Non-premium
            if (!isPremium) {
                // Keep only one select
                if (selects.length > 1) {
                    selects.splice(0, selects.length - 1)
                }
            }
            // One item should be primary
            if (selects.every(item => !item.primary)) {
                selects[0].primary = true;
            }
        }
        // Check to update primary and isDisabled keys
        if (select.action === 'remove-value') {
            setOptions(prevState => prevState.map(item => {
                if (item.options?.length) {
                    // Find the removed item
                    const i = item.options.findIndex(option => option.value === select.removedValue.value);
                    // Reset keys
                    if (i !== -1) {
                        item.options[i].isDisabled = false;
                        item.options[i].primary = false;
                    }
                }
                return item;
            }));
            updateTotals(prevState => --prevState);
        }
        const checkedSelects = checkSelects(selects);
        setDefaultValues(checkedSelects);
        changed(checkedSelects);
    }

    return (
        <>
            <AsyncSelect key={selectKey}
                         ref={(el) => {
                             selectRef.current = el;
                             // Open menu on the values container's space part
                             selectRef.current?.inputRef.parentNode?.parentNode?.addEventListener('click', (e) => {
                                 if (e.target.classList.contains("dms-n-config-table-react-select__value-container")) {
                                     selectRef.current?.focus();
                                     selectRef.current?.openMenu('first');
                                 }
                             });
                             // Open menu on the input container click
                             selectRef.current?.inputRef.parentNode?.addEventListener('click', () => {
                                 selectRef.current?.focus();
                                 selectRef.current?.openMenu('first');
                             });
                         }}
                         cacheOptions
                         defaultOptions={options}
                         loadOptions={loadOptions}
                         onChange={onSelectsChange}
                         defaultValue={defaultValues}
                         components={{
                             Group: (props) => <Group props={props} loadMore={loadMore} debug={debug}/>,
                             MultiValueLabel: (props) => <MultiValueLabel props={props} makePrimary={makePrimary} isPremium={isPremium}/>,
                             SingleValue,
                             LoadingIndicator,
                         }}
                         className="dms-n-config-table-react-select dms-n-config-table-react-select__multi-value__label"
                         classNamePrefix="dms-n-config-table-react-select"
                         placeholder={__('The choice is yours.', 'domain-mapping-system')}
                         onMenuOpen={() => menuOpened.current = true}
                         onMenuClose={() => {
                             // selectRef.current?.blur();
                             menuOpened.current = false;
                             // Reset menu items to the main state
                             if (Array.isArray(defaultObjects)) {
                                 setOptions(parseSearchedDataToGroupOptions(defaultObjects, debug));
                             }
                         }}
                         closeMenuOnSelect={!isPremium}
                         openMenuOnClick={false}
                         openMenuOnFocus={false}
                         isRtl={isRtl}
                         isMulti={isPremium}
                         isClearable={true}
                         hideSelectedOptions={false}/>
            <LoadMoreValues hasMore={hasMoreValues} getValues={getMoreValues}/>
        </>
    )
}