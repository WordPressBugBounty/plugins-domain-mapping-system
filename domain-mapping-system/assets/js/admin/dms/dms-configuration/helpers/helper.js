/**
 * Sprintf
 *
 * @param {string} format Text format
 * @param {array} args Arguments
 * @return {string}
 */
export const sprintf = (format, ...args) => {
    return format.replace(/{(\d+)}/g, function (match, number) {
        return typeof args[number] != 'undefined' ? args[number] : match;
    });
}

/**
 * Translate text
 *
 * @param {string} text Text
 * @return {string}
 */
export const $tr = (text) => {
    return dms_fs?.translations && dms_fs.translations[text] ? dms_fs.translations[text] : text;
}

/**
 * Value based on option data
 *
 * @param {object} data Data
 * @return {`${string}-${string}`}
 */
export const optionValue = (data) => `${data.objectId}-${data.type}`;

/**
 * Value based on DB data
 *
 * @param {object} data Data
 * @return {`${string}-post`|`${string}-${*}`|`${*}-post`|`${*}-${*}`}
 */
export const dataValue = (data) => `${data?.object_id}-${data?.object_type}`;

/**
 * Convert data to select value
 *
 * @param {object} data Mapping value
 * @return {{mappedLink: string, link: string, id: number, label: string, type: string, value: number, primary: boolean}}
 */
export const dataValueToSelectValue = (data) => ({
    id: data.value?.id,
    objectId: data.value?.object_id,
    value: dataValue(data.value),
    type: data.value?.object_type,
    label: data._object?.title,
    link: data._object?.link,
    mappedLink: data._mapped_link,
    primary: !!data.value?.primary,
});

/**
 * Parse searched data to select option
 *
 * @param {object} data Searched data
 * @param {object[]} selectedData Selected data
 * @return {{link, label, isDisabled: {boolean}, type, value}}
 */
export const parseSearchedDataToOption = (data, selectedData) => {
    const value = optionValue({objectId: data.id, type: data.type});

    return {
        objectId: data.id,
        value,
        label: data.title,
        type: data.type,
        link: data.link,
        primary: false,
        isDisabled: selectedData.some(item => value === dataValue(item.value)),
    }
};

/**
 * Parse response data to UI options
 *
 * @param {object[]} data Data
 * @param {object[]} selectedData Selected data
 * @param {boolean} debug Debug
 * @return {*[]}
 */
export const parseSearchedDataToGroupOptions = (data, selectedData, debug) => {
    const optionsGroups = [];
    try {
        for (const groupData of data) {
            const groupName = groupData.object_group?.name || '';
            const group = {
                label: groupData.object_group?.label || '',
                name: groupName,
                pagination: groupData._objects?._pagination,
                key: `${groupName}:${groupData._objects?._pagination?.current_page}:${groupData._objects?._pagination?.per_page}`,
                options: [],
            };
            if (groupData._objects?.objects?.length) {
                for (const object of groupData._objects.objects) {
                    group.options.push(parseSearchedDataToOption(object, selectedData));
                }
            }
            optionsGroups.push(group);
        }
    } catch (e) {
        if (debug) {
            console.info('DMS: ' + __("Failed to parse data for group options.", 'domain-mapping-system'));
            console.error(e);
        }
    }
    return optionsGroups;
}

/**
 * Debounce
 *
 * @param {function} fn Callback
 * @param {number} delay Delay
 * @return {(function(...[*]): void)|*}
 */
export const debounce = (fn, delay = 250) => {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fn(...args);
        }, delay);
    };
}

/**
 * Make unique key
 *
 * @param {number} length Length of the key
 * @return {string}
 */
export const makeUniqueKey = (length = 8) => {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+~`[];?,';
    const charactersLength = characters.length;
    let result = '', counter = 0;
    while (counter++ < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}