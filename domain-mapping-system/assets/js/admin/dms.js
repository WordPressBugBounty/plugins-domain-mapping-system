import React from 'react';
import {createRoot} from 'react-dom/client';
import DMSConfiguration from "./dms/dms-configuration/DMSConfiguration";
import AdditionalOptions from "./dms/additional-options/AdditionalOptions";
import AvailablePostTypes from "./dms/available-post-types/AvailablePostTypes";

window.addEventListener('DOMContentLoaded', () => {
    const dms = document.querySelector('.dms-n');
    if (!dms) {
        // No container found to initialize
        return;
    }
    const dmsRoot = createRoot(dms);

    const debugModeOn = true;

    const isPremium = dms_data.is_premium === '0' ? false : dms_data.is_premium === '1';
    const upgradeUrl = dms_data.upgrade_url;
    const restUrl = dms_data.rest_url;
    const restNonce = dms_data.rest_nonce;
    const mapsPaged = dms_data.paged;
    const mapsPerPage = dms_data.mappings_per_page;
    const valuesPerMapping = dms_data.values_per_mapping;
    const availableObjects = dms_data.available_objects;
    // Remove global variable
    Object.keys(dms_data).forEach(key => delete dms_data[key]);

    dmsRoot.render(
        <React.StrictMode>
            <div className="dms-n-row dms-n-config">
                <DMSConfiguration isPremium={isPremium} upgradeUrl={upgradeUrl} restUrl={restUrl}
                                  restNonce={restNonce} mapsPaged={mapsPaged} mapsPerPage={mapsPerPage}
                                  valuesPerPage={valuesPerMapping} debug={debugModeOn}/>
            </div>
            <div className="dms-n-row dms-n-additional">
                <AdditionalOptions isPremium={isPremium} upgradeUrl={upgradeUrl} restUrl={restUrl}
                                   restNonce={restNonce} debug={debugModeOn}/>
            </div>
            <div className="dms-n-row dms-n-post-types">
                <AvailablePostTypes availableObjects={availableObjects} isPremium={isPremium} upgradeUrl={upgradeUrl}
                                    restUrl={restUrl} restNonce={restNonce} debug={debugModeOn}/>
            </div>
        </React.StrictMode>
    );
});
