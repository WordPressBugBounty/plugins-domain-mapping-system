import {components} from "react-select";
import {Icon, external, globe, home} from '@wordpress/icons';

export default function MultiValueLabel({makePrimary, isPremium, props}) {
    /**
     * Make the value primary
     */
    const makeThisPrimary = () => {
        makePrimary(props.data.value);
    }

    return <>
        <components.MultiValueLabel {...props}>{props.children}</components.MultiValueLabel>
        {isPremium && <div className="dms-n-config-table-react-select__multi-value__radio">
            <Icon icon={home} className={`${props.data.primary ? 'primary' : ''}`} onClick={makeThisPrimary}/>
        </div>}
        {props.data.link &&
            <a className="dms-n-config-table-react-select__multi-value__link" href={props.data.link} target="_blank">
                <Icon icon={external}/>
            </a>}
        {props.data.mappedLink &&
            <a className="dms-n-config-table-react-select__multi-value__link" href={props.data.mappedLink}
               target="_blank">
                <Icon icon={globe}/>
            </a>}
    </>
}