/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { InnerBlocks, RichText, InspectorControls } from '@wordpress/block-editor';

registerBlockType('fiftyonedegrees/conditional-group-block', {
    title: '51Degrees Conditional Group Block',
    icon: 'media-default',
    category: '51Degrees',
    attributes: {
        property: {
            type: 'string'
        },
        operator: {
            type: 'string'
        },
        value: {
            type: 'string'
        }
    },
    edit: (props) => {
        const {
            attributes: { property, operator, value },
            setAttributes,

        } = props;

        return (
            <Fragment>

                <InspectorControls>

                    <SelectControl
                        label="Property"
                        selected={property}
                        options={fiftyoneProperties}
                        onChange={(property) => {
                            setAttributes({ property: property });
                        }}
                    />

                    <SelectControl
                        label="Operator"
                        selected={operator}
                        options={[
                            { label: 'Operator', value: '' },
                            { label: 'Is', value: 'is' },
                            { label: 'Contains', value: 'contains' },
                            { label: 'Is not', value: 'not' },
                        ]}
                        onChange={(operator) => {
                            setAttributes({ operator: operator });
                        }}
                    />
                    <TextControl
                        label="Value"
                        value={value}
                        onChange={(value) => {
                            setAttributes({ value: value });
                        }}
                    />
                </InspectorControls>

                <div class="fiftyonedegrees-conditional-block">
                    <div class="fiftyonedegrees-condition">Showing when <b>{property}</b> {operator} <b>{value}</b></div>
                    <InnerBlocks />
                </div>
            </Fragment>
        );
    },
    save: () => {
        return (
            <Fragment>
                <InnerBlocks.Content />
            </Fragment>
        );
    },
});
