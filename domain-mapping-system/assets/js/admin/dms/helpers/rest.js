/**
 * Get mappings
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} paged Page
 * @param {number} perPage Per page
 * @param {boolean} includeMappings Include mappings
 * @return {Promise<any>}
 */
export const getMappings = (restUrl, restNonce, paged = 1, perPage = 20, includeMappings = true) => {
    return fetch(`${restUrl}mappings?${includeMappings ? 'include[]=mapping_values&include[]=mapping_metas&meta_keys[]=locale&' : ''}paged=${paged}&limit=${perPage}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Get mapping values
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} id Mapping ID
 * @param {number} paged Page
 * @param {number} perPage Per page
 * @return {Promise<any>}
 */
export const getMappingValues = (restUrl, restNonce, id, paged = 1, perPage = 20) => {
    return fetch(`${restUrl}mappings/${id}/values?include[]=mapped_link&include[]=object&paged=${paged}&per_page=${perPage}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Get languages
 *
 * @returns {Promise<any>}
 */
export const fetchLanguages = (restUrl, restNonce) => {
    return fetch(`${restUrl}languages`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Create mapping
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {object} data Domain mapping data
 * @return {Promise<any>}
 */
export const createMapping = (restUrl, restNonce, data) => {
    // Request body
    const body = {host: data.host};
    // Path
    if (data.path) {
        body.path = data.path;
    }
    // Custom html
    if (data.customHtml) {
        body.custom_html = data.customHtml;
    }
    // Attachment
    if (data.favicon?.id) {
        body.attachment_id = data.favicon.id;
    }
    // Send request
    return fetch(`${restUrl}mappings/`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(body)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Create mapping values
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} id Mapping ID
 * @param {object[]} data Domain mapping values
 * @return {Promise<any>}
 */
export const createMappingValues = (restUrl, restNonce, id, data) => {
    // Request data
    const requestData = data.map(item => ({
        mapping_id: id,
        object_type: item.type,
        object_id: item.objectId,
        primary: +item.primary,
    }));
    // Send request
    return fetch(`${restUrl}mapping_values/batch`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify([
            {
                method: 'create',
                data: requestData,
            }
        ])
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Update mapping
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} id Mapping ID
 * @param {object[]} data Domain mapping values
 * @return {Promise<any>}
 */
export const updateMapping = (restUrl, restNonce, id, data) => {
    const body = {};
    // Host
    if ('host' in data) {
        body.host = data.host;
    }
    // Path
    if ('path' in data) {
        body.path = data.path;
    }
    // Custom html
    if ('customHtml' in data) {
        body.custom_html = data.customHtml;
    }
    // Favicon
    if ('favicon' in data) {
        body.attachment_id = data.favicon?.id || null;
    }
    // Send request
    return fetch(`${restUrl}mappings/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(body)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Update mapping metadata
 *
 * @param restUrl
 * @param restNonce
 * @param mappingId
 * @param createData
 * @param updateData
 * @param deleteData
 * @returns {Promise<any>}
 */
export const mappingMetaBatch = (restUrl, restNonce, mappingId, createData, updateData, deleteData) => {
    const body = [];
    if (createData){
        body.push({
            method: 'create',
            data: createData
        })
    }
    if (updateData) {
        body.push({
            method: 'update',
            data: updateData
        })
    }
    if (deleteData){
        body.push({
            method: 'delete',
            data: deleteData
        })
    }
    return fetch(`${restUrl}mappings/${mappingId}/mapping_metas/batch`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(body)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    })
}

/**
 * Update mapping
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} id Mapping ID
 * @param {object[]} data Domain mapping values
 * @param {object[]} deleteData Domain mapping values to delete
 * @return {Promise<any>}
 */
export const updateMappingValues = (restUrl, restNonce, id, data, deleteData) => {
    // Request data
    const createData = [];
    const updateData = [];
    // Check data for create/update
    for (const item of data) {
        const reqItem = {
            mapping_id: id,
            object_type: item.type,
            object_id: item.objectId,
            primary: +(!!item.primary),
        };
        if (item.id) {
            updateData.push({
                id: item.id,
                ...reqItem,
            });
        } else {
            createData.push(reqItem);
        }
    }
    const requestData = [];
    // Create
    if (createData.length) {
        requestData.push({
            method: "create",
            data: createData,
        });
    }
    // Update
    if (updateData.length) {
        requestData.push({
            method: "update",
            data: updateData,
        });
    }
    // Delete
    if (deleteData.length) {
        requestData.push({
            method: "delete",
            data: deleteData,
        });
    }
    // Send request
    return fetch(`${restUrl}mapping_values/batch`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(requestData)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Delete mapping
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number} id Mapping ID
 * @return {Promise<void>}
 */
export const deleteMapping = (restUrl, restNonce, id) => {
    return fetch(`${restUrl}mappings/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce,
        },
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Delete mappings
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {number[]} ids Mappings IDs
 * @return {Promise<void>}
 */
export const deleteMappings = (restUrl, restNonce, ids) => {
    return fetch(`${restUrl}mappings/batch`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce,
        },
        body: JSON.stringify([
            {
                method: 'delete',
                data: ids,
            }
        ]),
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Search objects
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {string} s Search term
 * @param {number} page Page
 * @param {number} perPage Per page
 * @return {Promise<any>}
 */
export const searchObject = (restUrl, restNonce, s, page = 1, perPage = 5) => {
    return fetch(`${restUrl}object_groups?include[]=objects&s=${s}&per_page=${perPage}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Load more objects
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {string} group Group of type
 * @param {string} s Search term
 * @param {number} page Page
 * @param {number} perPage Per page
 * @return {Promise<any>}
 */
export const loadMoreObjects = (restUrl, restNonce, group, s, page = 1, perPage = 5) => {
    return fetch(`${restUrl}object_groups/${group}/objects?s=${s}&page=${page}&per_page=${perPage}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Get setting by key
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {string} key Setting key
 * @return {Promise<any>}
 */
export const getSetting = (restUrl, restNonce, key) => {
    return fetch(`${restUrl}settings/${key}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Get settings
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {string[]} keys Keys
 * @return {Promise<any>}
 */
export const getSettings = (restUrl, restNonce, keys) => {
    return fetch(`${restUrl}settings?${keys.map(key => 'key_names[]=' + key).join('&')}`, {
        headers: {
            'X-WP-Nonce': restNonce
        }
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Update setting by key
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {{key: string, value: any}} data Setting data
 * @return {Promise<any>}
 */
export const updateSetting = (restUrl, restNonce, data) => {
    return fetch(`${restUrl}settings/${data.key}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(data)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}

/**
 * Create/update settings
 *
 * @param {string} restUrl Rest url
 * @param {string} restNonce Rest nonce
 * @param {object[]} data Data
 * @return {Promise<any>}
 */
export const updateSettings = (restUrl, restNonce, data) => {
    return fetch(`${restUrl}settings/batch`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify(data)
    }).then(res => {
        const data = res.json();
        if (!res.ok) {
            throw new Error(data.message ? data.message : "Something went wrong");
        }
        return data;
    });
}