import {components} from "react-select";
import {Icon, external, globe} from "@wordpress/icons";

export default function SingleValue({children, data, ...props}) {
    return <components.SingleValue {...props}>
        <span>{children}</span>
        {data.mappedLink &&
            <a className="dms-n-config-table-react-select__single-value__link" href={data.mappedLink}
               target="_blank">
                <Icon icon={globe}/>
            </a>}
        {data.link &&
            <a className="dms-n-config-table-react-select__single-value__link" href={data.link}
               target="_blank">
                <Icon icon={external}/>
            </a>}
    </components.SingleValue>
}