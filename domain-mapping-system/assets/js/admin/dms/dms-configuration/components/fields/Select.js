import {__, isRTL} from "@wordpress/i18n";
import AsyncSelect from "react-select/async";
import {useEffect, useRef, useState} from "react";
import MultiValueLabel from "./Select/MultiValueLabel"
import SingleValue from "./Select/SingleValue"
import LoadingIndicator from "./Select/Loading"
import Group from "./Select/Group"
import Input from "./Select/Input";
import {loadMoreObjects, searchObject} from "../../../helpers/rest";
import {dataValue, dataValueToSelectValue, debounce, optionValue, parseSearchedDataToGroupOptions, parseSearchedDataToOption} from "../../helpers/helper";

export default function Select({selectedData, defaultObjects, changed, totalValues, updateTotals, getMoreValues, rendered, restUrl, restNonce, isPremium, debug}) {
    const isRtl = isRTL();
    const [hasMoreValues, setHasMoreValues] = useState(false);
    const [defaultValues, setDefaultValues] = useState([]);
    const [options, setOptions] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectKey, setSelectKey] = useState(0);
    const selectRef = useRef(null);
    const initMenu = useRef(0);
    const [menuOpened, setMenuOpened] = useState(false);

    useEffect(() => {
        if (Array.isArray(defaultObjects)) {
            setOptions(parseSearchedDataToGroupOptions(defaultObjects, selectedData, debug));
            rendered();
        }
    }, [defaultObjects]);

    useEffect(() => {
        // Update saved data (to show globe icon automatically)
        const newDefaultValuesState = [
            ...selectedData.map(dataValueToSelectValue),
            ...defaultValues.filter(obj => selectedData.findIndex(item => optionValue(obj) === dataValue(item.value)) === -1),
        ];
        setDefaultValues(newDefaultValuesState);
        changed(newDefaultValuesState, true);
        setSelectKey((selectKey + 1) % 2);
    }, [selectedData]);

    useEffect(() => {
        setHasMoreValues(totalValues > defaultValues.filter(obj => obj.id).length);
    }, [totalValues, defaultValues]);

    /**
     * Make an item primary
     *
     * @param {string|number} value Option value
     */
    const makePrimary = (value) => {
        const selects = defaultValues.map(data => {
            data.primary = data.value === value;
            return data;
        });
        setDefaultValues(selects);
        changed(selects);
    }

    /**
     * Get options
     *
     * @param {string} inputValue Search term
     * @param {function} callback Resolver callback
     */
    const getOptions = (inputValue, callback) => {
        setSearchTerm(inputValue);
        // Reset init
        initMenu.current = 0;
        // Search objects
        return searchObject(restUrl, restNonce, inputValue).then(data => {
            const foundOptions = parseSearchedDataToGroupOptions(data, selectedData, debug);
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
    const loadOptions = React.useCallback(debounce(getOptions), []);

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
                        options: [...group.options, ...data.objects.map(item => parseSearchedDataToOption(item, selectedData))]
                    }
                }
                return group;
            });
            setOptions(newOptions);
            const resetDropdown = !initMenu.current++;
            resetDropdown && setSelectKey((selectKey + 1) % 2);
            // Check for more pages
            return {
                hasMorePages: data._pagination?.total_pages > page,
                callback: resetDropdown ? () => setTimeout(() => setMenuOpened(true), 10) : null,
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
        setDefaultValues(selects);
        changed(selects);
    }

    return (
        <AsyncSelect key={selectKey}
                     ref={(el) => selectRef.current = el}
                     cacheOptions
                     defaultOptions={options}
                     loadOptions={loadOptions}
                     onChange={onSelectsChange}
                     defaultValue={defaultValues}
                     components={{
                         Group: (props) => <Group props={props} loadMore={loadMore} debug={debug}/>,
                         MultiValueLabel: (props) => <MultiValueLabel props={props} makePrimary={makePrimary} isPremium={isPremium}/>,
                         Input: (props) => <Input props={props} hasMore={hasMoreValues} getValues={getMoreValues} toggleMenu={() => !menuOpened && setMenuOpened(true)} isPremium={isPremium}/>,
                         SingleValue,
                         LoadingIndicator,
                     }}
                     className="dms-n-config-table-react-select"
                     classNamePrefix="dms-n-config-table-react-select"
                     placeholder={__('The choice is yours.', 'domain-mapping-system')}
                     onMenuOpen={() => setMenuOpened(true)}
                     onMenuClose={() => {
                         selectRef.current?.blur();
                         setMenuOpened(false);
                     }}
                     menuIsOpen={menuOpened}
                     closeMenuOnSelect={!isPremium}
                     openMenuOnClick={false}
                     openMenuOnFocus={false}
                     isRtl={isRtl}
                     isMulti={isPremium}
                     isClearable={true}
                     hideSelectedOptions={false}/>
    )
}