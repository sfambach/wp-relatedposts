( function ( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;

    blocks.registerBlockType( 'fambach/related-posts', {
        edit: function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var sidebarElements = [
                el( ToggleControl, {
                    label: 'Verwandte Beiträge aktivieren',
                    checked: attributes.beitraege_aktivieren,
                    onChange: function ( value ) {
                        setAttributes( { beitraege_aktivieren: value } );
                    },
                } )
            ];

            if ( attributes.beitraege_aktivieren ) {
                sidebarElements.push(
                    el( SelectControl, {
                        label: 'Anzahl der Beiträge',
                        value: attributes.anzahl_beitraege,
                        options: [
                            { label: '3 Beiträge', value: 3 },
                            { label: '5 Beiträge', value: 5 },
                            { label: '10 Beiträge', value: 10 },
                            { label: '15 Beiträge', value: 15 },
                            { label: '20 Beiträge', value: 20 },
                            { label: '25 Beiträge', value: 25 },
                            { label: '30 Beiträge', value: 30 },
                            { label: '35 Beiträge', value: 35 },
                            { label: '40 Beiträge', value: 40 },
                            { label: '45 Beiträge', value: 45 },
                            { label: '50 Beiträge', value: 50 }
                        ],
                        onChange: function ( value ) {
                            setAttributes( { anzahl_beitraege: parseInt( value, 10 ) } );
                        },
                    } )
                );

                sidebarElements.push(
                    el( SelectControl, {
                        label: 'Art der Überschrift',
                        value: attributes.ueberschrift_typ,
                        options: [
                            { label: 'Überschrift 2 (H2)', value: 'h2' },
                            { label: 'Überschrift 3 (H3)', value: 'h3' },
                            { label: 'Überschrift 4 (H4)', value: 'h4' },
                            { label: 'Überschrift 5 (H5)', value: 'h5' },
                            { label: 'Überschrift 6 (H6)', value: 'h6' },
                            { label: 'Normaler Text (P)', value: 'p' },
                            { label: 'Container (DIV)', value: 'div' }
                        ],
                        onChange: function ( value ) {
                            setAttributes( { ueberschrift_typ: value } );
                        },
                    } )
                );
            }

            return [
                el( InspectorControls, { key: 'setting' },
                    el( PanelBody, { title: 'Block-Einstellungen', initialOpen: true },
                        sidebarElements
                    )
                ),
                el( 'div', { className: props.className + ' related-posts-admin-preview' }, 
                    attributes.beitraege_aktivieren 
                        ? '✓ Verwandte Beiträge aktiv (Vorschau zeigt max. ' + attributes.anzahl_beitraege + ' Beiträge mit <' + attributes.ueberschrift_typ + '> Überschrift).'
                        : '🛑 Verwandte Beiträge ist deaktiviert.'
                )
            ];
        },
        save: function () {
            return null;
        },
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );
