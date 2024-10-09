import {useEffect, useState} from "react";
import {__, isRTL, sprintf} from "@wordpress/i18n";
import Select from "react-select";
import {getMappings} from "../../../helpers/rest";
import {components} from "react-select";

export default function GlobalDomainMappingRow({slug, slugMaps, value, selectValue, updateValue, restUrl, restNonce, isPremium, upgradeUrl, loading}) {
    const isRtl = isRTL();
    const [mappings, setMappings] = useState([]);
    const [showSelect, setShowSelect] = useState(false);
    const [defaultValue, setDefaultValue] = useState([]);

    useEffect(() => {
        loadMappings();
    }, []);

    /**
     * Load mappings
     */
    const loadMappings = () => {
        loading(true);
        setShowSelect(false);
        getMappings(restUrl, restNonce, 1, -1).then(data => {
            const mappingsData = data.items.filter(domainMap => domainMap.mapping?.host && domainMap._values?.items?.length).map(domainMap => ({
                value: domainMap.mapping.id,
                label: `${domainMap.mapping.host}${domainMap.mapping.path ? '/' + domainMap.mapping.path : ''}`,
            }));
            setMappings(mappingsData);
            // Set default values
            const values = [];
            for (const mappingId of selectValue) {
                const i = mappingsData.findIndex(mapping => mapping.value === +mappingId);
                if (i !== -1) {
                    values.push({
                        value: +mappingId,
                        label: mappingsData[i].label,
                    });
                }
            }
            setDefaultValue(values);
            // Show entriesState
            setShowSelect(true);
            loading(false);
        }).catch(e => {
            debug && console.error(e);
            // Hide loading
            loading(false);
        });
    }

    /**
     * On checkbox change
     */
    const changed = () => {
        updateValue(prevState => ({
            ...prevState,
            [slug]: {
                ...prevState[slug],
                value: !value ? 'on' : false,
                changed: true,
            },
        }));
    }

    /**
     * On select change
     *
     * @param {object[]} selects Selects
     * @param {object} select Select action info
     */
    const onSelectsChange = (selects, select) => {
        updateValue(prevState => ({
            ...prevState,
            [slugMaps]: {
                ...prevState[slugMaps],
                value: selects.map(mapping => mapping.value),
                changed: true,
            },
        }));
    }

    /**
     * Select option
     *
     * @param {string} children Option label
     * @param {object} props Option data
     * @return {JSX.Element}
     * @constructor
     */
    const Option = ({children, ...props}) => {
        return <components.Option {...props}>
            <label>
                <input type="checkbox" onChange={() => {}} checked={props.isSelected}/>
                {children}
            </label>
        </components.Option>;
    };

    const MultiValueRemove = (props) => '';

    return <li>
        <div className="dms-n-additional-accordion-li">
            <div className="dms-n-additional-accordion-checkbox">
                <input className="checkbox" type="checkbox" disabled={!isPremium}
                       checked={isPremium && value === 'on'} onChange={changed}/>
            </div>
            <div className="dms-n-additional-accordion-content">
                <span className="label">
                    <strong>{__("Global Domain Mapping", 'domain-mapping-system')}</strong> - {__("Enable Global Domain Mapping, which means that all pages will be served for all mapped domains.", 'domain-mapping-system')}
                </span>
                {showSelect && mappings.length > 1 && <>
                    &nbsp;<span
                        className="label">{__("Select the domain [+path] to serve for all unmapped pages:", 'domain-mapping-system')}</span>
                    &nbsp;<Select defaultValue={defaultValue}
                                  options={mappings}
                                  onChange={onSelectsChange}
                                  components={{Option, MultiValueRemove}}
                                  className="dms-n-additional-react-select"
                                  classNamePrefix="dms-n-additional-react-select"
                                  placeholder={__("Select domain(s)", 'domain-mapping-system')}
                                  isDisabled={!isPremium}
                                  isClearable={true}
                                  isRtl={isRtl}
                                  isSearchable={true}
                                  isMulti={true}
                                  hideSelectedOptions={false}
                                  closeMenuOnSelect={false}/>.
                    <span className="label"
                          dangerouslySetInnerHTML={{__html: "&nbsp;" + sprintf(__("Read more in our %sdocumentation%s.", 'domain-mapping-system'), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com/features/global-domain-mapping">', '</a>')}}></span>
                    {!isPremium && <>
                        &nbsp;
                        <a className="upgrade" href={upgradeUrl}>{__("Upgrade", 'domain-mapping-system')} &#8594;</a>
                    </>}
                </>}
            </div>
        </div>
    </li>
}