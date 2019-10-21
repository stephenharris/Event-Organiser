'use strict';
import LicenseField from './license-field';

class Licenses extends React.Component {
  constructor(props) {
    super(props);
    console.log(props);
    console.log(this.props.extensions)
  }

  render() {
      
    return (
        <table className="form-table">
            <tbody>
            {this.props.extensions.map((extension) => {
                return (
                    <tr key={extension.id}>
                        <th scope="row">
                            <label htmlFor={extension.id}>{extension.label}</label>
                        </th>
                        <td>
                            <LicenseField
                                id={extension.id}
                                slug={extension.slug}
                                license={extension.key}
                                status={extension.status}
                                expires={extension.expires}
                            />
                        </td>
                    </tr>
                )
            })}
            </tbody>
        </table>
        
    );
  }
}
const domContainer = document.querySelector('#eo-licenses');
console.log(eoLicenses);
ReactDOM.render(wp.element.createElement(Licenses, {
    extensions: eoLicenses.extensions
}), domContainer);