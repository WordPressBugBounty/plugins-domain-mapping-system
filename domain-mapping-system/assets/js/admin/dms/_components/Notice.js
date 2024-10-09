import {useEffect, useState} from 'react';
import {__} from "@wordpress/i18n";

export default function Notice({index, dismiss, data}) {
    const [show, setShow] = useState(true);

    useEffect(() => {
        if (data.type === 'success') {
            // Hide after 5 seconds
            setTimeout(hide, 5000);
        }
    }, []);

    /**
     * Hide notice
     */
    const hide = () => {
        setShow(false);
        setTimeout(() => {
            dismiss(index);
        }, 500);
    }

    return (
        <div className={`notice notice-${data.type} is-dismissible${show ? ' dms-fade-in' : ' dms-fade-out'}`}>
            <p dangerouslySetInnerHTML={{__html: data.message}}></p>
            <button type="button" className="notice-dismiss" onClick={hide}>
                <span className="screen-reader-text">{__("Dismiss this notice.", 'domain-mapping-system')}</span>
            </button>
        </div>
    );
}