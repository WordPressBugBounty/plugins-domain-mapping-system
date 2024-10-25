import React, {useState, useEffect} from 'react';
import PropTypes from 'prop-types';
import {__} from "@wordpress/i18n";


export default function LanguageDropdown({isPremium, selectedLocale, languages, changed}) {
    const [isOpen, setIsOpen] = useState(false);
    const [currentLanguage, setCurrentLanguage] = useState(null);

    const toggleDropdown = () => {
        setIsOpen(!isOpen);
    };

    const handleItemClick = (language) => {
        if (language === null) {
            language = {
                locale: null,
                flag_url: null,
                display_name: 'Select Language'
            }
        }
        setCurrentLanguage(language);
        changed(language);
        setIsOpen(false);
    };

    const handleKeyDown = (evt) => {
        if (isOpen) {
            const items = document.querySelectorAll('.dms-n-item');
            let index = Array.from(items).findIndex(item => item.classList.contains('dms-n-selected'));

            if (evt.key === "ArrowDown") {
                index = (index + 1) % items.length;
                items[index].focus();
                evt.preventDefault();
            } else if (evt.key === "ArrowUp") {
                index = (index - 1 + items.length) % items.length;
                items[index].focus();
                evt.preventDefault();
            } else if (evt.key === "Enter") {
                handleItemClick(languages[index]);
            }
        }
    };

    const handleKeyUp = (evt) => {
        if (evt.key === "Escape") {
            setIsOpen(false);
        }
    };

    const handleClickOutside = (evt) => {
        if (!evt.target.closest(".dms-n-caption")) {
            setIsOpen(false);
        }
    };

    useEffect(() => {
        const selectedLanguage = languages.filter(language => language.locale === selectedLocale);
        if (selectedLanguage.length) {
            setCurrentLanguage(selectedLanguage[0]);
        }
        document.addEventListener("keyup", handleKeyUp);
        document.addEventListener("keydown", handleKeyDown);
        document.addEventListener("click", handleClickOutside);
        return () => {
            document.removeEventListener("keyup", handleKeyUp);
            document.removeEventListener("keydown", handleKeyDown);
            document.removeEventListener("click", handleClickOutside);
        };
    }, [selectedLocale, languages]);

    return (
        <div className={`dms-n-language-switcher ${!isPremium ? 'dms-n-language-switcher-disabled' : ''}`}>
            <div className={`dms-n-dropdown ${isOpen ? "open" : ""}`} role="combobox" aria-expanded={isOpen}
                 aria-haspopup="listbox">
                <div
                    className="dms-n-caption"
                    onClick={toggleDropdown}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => e.key === 'Enter' && toggleDropdown()}
                    aria-label={__('Select Language', 'domain-mapping-system')}
                >
                    {currentLanguage && currentLanguage.flag_url ? (
                        <>
                            <img src={currentLanguage.flag_url} alt={currentLanguage.display_name} width="20"/>
                            {currentLanguage.display_name}
                        </>
                    ) : (
                        <div className="dms-n-dropdown-label">{__('Select Language', 'domain-mapping-system')}:</div>
                    )}
                </div>
                <svg height="20" width="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"
                     className="dms-n-arrow">
                    <path
                        d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z"/>
                </svg>

                {isOpen && (
                    <div className="dms-n-list" role="listbox">
                        <div
                            className={`dms-n-item ${!currentLanguage ? "dms-n-selected" : ""}`}
                            data-item="none"
                            onClick={() => handleItemClick(null)}
                        >
                            <div className="dms-n-dropdown-label">{__('None', 'domain-mapping-system')}</div>
                        </div>
                        {languages.map((language) => (
                            <div
                                key={language.locale}
                                className={`dms-n-item ${currentLanguage?.locale === language.locale ? "dms-n-selected" : ""}`}
                                data-item={language.locale}
                                onClick={() => handleItemClick(language)}
                                tabIndex={0} // Make item focusable
                                onKeyDown={(e) => e.key === 'Enter' && handleItemClick(language)} // Handle Enter key
                            >
                                <img src={language.flag_url} alt={language.display_name} width="20"/>
                                <div className="dms-n-dropdown-label">{language.display_name}</div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

LanguageDropdown.propTypes = {
    selectedLocale: PropTypes.string.isRequired,
    languages: PropTypes.arrayOf(
        PropTypes.shape({
            locale: PropTypes.string.isRequired,
            display_name: PropTypes.string.isRequired,
            flag_url: PropTypes.string.isRequired,
        })
    ).isRequired,
    changed: PropTypes.func.isRequired,
};
