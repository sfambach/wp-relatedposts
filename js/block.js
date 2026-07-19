( function ( blocks, element, blockEditor, components, data ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var FormTokenField = components.FormTokenField;
    var useSelect = data.useSelect;

    blocks.registerBlockType( 'fambach/related-posts', {
        title: 'Related Posts',
        icon: 'list-view',
        category: 'widgets',
        edit: function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // 1. Sicheres Laden der Daten des aktuellen Beitrags
            var postData = useSelect( function( select ) {
                var editor = select( 'core/editor' );
                return {
                    id: editor ? editor.getCurrentPostId() : null,
                    categories: editor ? ( editor.getEditedPostAttribute( 'categories' ) || [] ) : []
                };
            }, [] );

            // 2. ALLE verfügbaren Kategorien laden
            var allWpCategories = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'category', { per_page: -1 } ) || [];
            }, [] );

            var catIdToName = {};
            var catNameToId = {};
            var allCategoryNames = [];
            
            if ( Array.isArray( allWpCategories ) ) {
                allCategoryNames = allWpCategories.map( function( cat ) {
                    catIdToName[cat.id] = cat.name;
                    catNameToId[cat.name] = cat.id;
                    return cat.name;
                } );
            }

            var aktuelleKategorieIds = attributes.kategorie_modus === 'manuell' 
                ? ( attributes.eigene_kategorien_ids || [] )
                : postData.categories;

            // 3. REST-API Abfrage für die Live-Vorschau
            var relatedPostsRecords = useSelect( function( select ) {
                if ( ! aktuelleKategorieIds || aktuelleKategorieIds.length === 0 ) {
                    return [];
                }
                var queryArgs = {
                    categories: aktuelleKategorieIds,
                    per_page: attributes.anzahl_beitraege || 5
                };
                if ( attributes.kategorie_modus === 'automatisch' && postData.id ) {
                    queryArgs.exclude = [ postData.id ];
                }
                return select( 'core' ).getEntityRecords( 'postType', 'post', queryArgs );
            }, [ aktuelleKategorieIds, postData.id, attributes.anzahl_beitraege, attributes.kategorie_modus ] );

            var categoryNamesString = [];
            if ( Array.isArray( allWpCategories ) ) {
                categoryNamesString = allWpCategories.filter( function( cat ) {
                    return aktuelleKategorieIds.indexOf( cat.id ) !== -1;
                } ).map( function( cat ) { return cat.name; } ).join( ', ' );
            }

            var selectedTokens = [];
            if ( attributes.eigene_kategorien_ids ) {
                selectedTokens = attributes.eigene_kategorien_ids.map( function( id ) {
                    return catIdToName[id] || '';
                } ).filter( Boolean );
            }

            // Sidebar-Steuerung (Inspector)
            var sidebarElements = [
                el( ToggleControl, {
                    label: 'Verwandte Beiträge aktivieren',
                    checked: attributes.beitraege_aktivieren,
                    onChange: function ( value ) { setAttributes( { beitraege_aktivieren: value } ); }
                } )
            ];

            if ( attributes.beitraege_aktivieren ) {
                sidebarElements.push(
                    el( TextControl, {
                        label: 'Name der Überschrift',
                        value: attributes.ueberschrift_text || 'Verwandte Beiträge',
                        onChange: function ( value ) { setAttributes( { ueberschrift_text: value } ); }
                    } )
                );

                sidebarElements.push(
                    el( SelectControl, {
                        label: 'Typ der Überschrift',
                        value: attributes.ueberschrift_typ || 'h2',
                        options: [
                            { label: 'Überschrift 2 (H2)', value: 'h2' }, { label: 'Überschrift 3 (H3)', value: 'h3' },
                            { label: 'Überschrift 4 (H4)', value: 'h4' }, { label: 'Überschrift 5 (H5)', value: 'h5' },
                            { label: 'Überschrift 6 (H6)', value: 'h6' }, { label: 'Normaler Text (P)', value: 'p' },
                            { label: 'Container (DIV)', value: 'div' }
                        ],
                        onChange: function ( value ) { setAttributes( { ueberschrift_typ: value } ); }
                    } )
                );

                sidebarElements.push(
                    el( SelectControl, {
                        label: 'Anzahl der Beiträge',
                        value: attributes.anzahl_beitraege || 5,
                        options: [
                            { label: '5 Beiträge', value: 5 }, { label: '10 Beiträge', value: 10 },
                            { label: '15 Beiträge', value: 15 }, { label: '20 Beiträge', value: 20 },
                            { label: '25 Beiträge', value: 25 }, { label: '30 Beiträge', value: 30 },
                            { label: '35 Beiträge', value: 35 }, { label: '40 Beiträge', value: 40 },
                            { label: '45 Beiträge', value: 45 }, { label: '50 Beiträge', value: 50 }
                        ],
                        onChange: function ( value ) { setAttributes( { anzahl_beitraege: parseInt( value, 10 ) } ); }
                    } )
                );

                sidebarElements.push(
                    el( SelectControl, {
                        label: 'Kategorien heranziehen aus',
                        value: attributes.kategorie_modus || 'automatisch',
                        options: [
                            { label: 'Kategorien des aktuellen Beitrags', value: 'automatisch' },
                            { label: 'Eigene Kategorien festlegen', value: 'manuell' }
                        ],
                        onChange: function ( value ) { setAttributes( { kategorie_modus: value } ); }
                    } )
                );

                if ( attributes.kategorie_modus === 'manuell' ) {
                    sidebarElements.push(
                        el( FormTokenField, {
                            label: 'Eigene Kategorien auswählen',
                            value: selectedTokens,
                            suggestions: allCategoryNames,
                            onChange: function ( tokens ) {
                                var newIds = tokens.map( function( name ) { return catNameToId[name]; } ).filter( Boolean );
                                setAttributes( { eigene_kategorien_ids: newIds } );
                            }
                        } )
                    );
                }

                // --- NEUE EINSTELLUNGEN ---
                sidebarElements.push(
                    el( ToggleControl, {
                        label: 'Datum neben dem Titel anzeigen',
                        checked: attributes.datum_anzeigen || false,
                        onChange: function ( value ) { setAttributes( { datum_anzeigen: value } ); }
                    } )
                );

                if ( attributes.datum_anzeigen ) {
                    sidebarElements.push(
                        el( SelectControl, {
                            label: 'Datums-Typ wählen',
                            value: attributes.datum_typ || 'date',
                            options: [
                                { label: 'Erstelldatum', value: 'date' },
                                { label: 'Letzte Änderung', value: 'modified' }
                            ],
                            onChange: function ( value ) { setAttributes( { datum_typ: value } ); }
                        } )
                    );
                }

                sidebarElements.push(
                    el( ToggleControl, {
                        label: 'Aufzählungspunkte (Listen-Stil) anzeigen',
                        checked: attributes.punkte_anzeigen !== false, // default true
                        onChange: function ( value ) { setAttributes( { punkte_anzeigen: value } ); }
                    } )
                );

                sidebarElements.push(
                    el( ToggleControl, {
                        label: 'Debugging im Frontend anzeigen',
                        checked: attributes.debug_modus || false,
                        onChange: function ( value ) { setAttributes( { debug_modus: value } ); }
                    } )
                );
            }
            // Design der Info- und Vorschau-Box
            var boxStyle = {
                padding: '20px',
                borderRadius: '4px',
                fontFamily: 'sans-serif',
                fontSize: '14px',
                lineHeight: '1.6',
                boxSizing: 'border-box'
            };

            var content = [];

            if ( ! attributes.beitraege_aktivieren ) {
                boxStyle.border = '2px dashed #e53e3e';
                boxStyle.background = '#fde8e8';
                boxStyle.color = '#c53030';
                content.push( el( 'strong', { key: 'status' }, '🛑 Verwandte Beiträge sind deaktiviert.' ) );
            } else if ( aktuelleKategorieIds.length === 0 ) {
                boxStyle.border = '2px dashed #dd6b20';
                boxStyle.background = '#fffaf0';
                boxStyle.color = '#dd6b20';
                content.push( el( 'strong', { key: 'status' }, '⚠️ Warnung: ' ), 'Es wurden keine Kategorien gefunden oder ausgewählt.' );
            } else {
                boxStyle.border = '2px dashed #3182ce';
                boxStyle.background = '#ebf8ff';
                boxStyle.color = '#2b6cb0';

                content.push( el( 'div', { key: 'info-1', style: { marginBottom: '8px' } }, el( 'strong', null, '📊 Live-Analyse für diesen Beitrag:' ) ) );
                content.push( el( 'div', { key: 'info-2' }, '\u2022 Modus: ', el( 'strong', null, attributes.kategorie_modus === 'manuell' ? 'Manuell definiert' : 'Automatisch (Beitrag)' ) ) );
                content.push( el( 'div', { key: 'info-3' }, '\u2022 Herangezogene Kategorien (' + aktuelleKategorieIds.length + '): ', el( 'span', { style: { fontStyle: 'italic' } }, categoryNamesString || 'Lade Kategorien...' ) ) );
                
                var anzahlGefunden = relatedPostsRecords ? relatedPostsRecords.length : 0;
                content.push( el( 'div', { key: 'info-4', style: { marginBottom: '12px' } }, '\u2022 Verfügbare verwandte Beiträge in der DB: ', el( 'strong', null, relatedPostsRecords === null ? 'Lade...' : anzahlGefunden ) ) );

                var frontendPreviewElements = [];
                frontendPreviewElements.push(
                    el( attributes.ueberschrift_typ || 'h2', { 
                        key: 'preview-heading',
                        style: { margin: '0 0 10px 0', color: '#000', fontWeight: 'bold' }
                    }, attributes.ueberschrift_text || 'Verwandte Beiträge' )
                );

                if ( relatedPostsRecords === null ) {
                    frontendPreviewElements.push( el( 'p', { key: 'preview-loading', style: { fontStyle: 'italic', color: '#718096' } }, 'Beiträge werden geladen...' ) );
                } else if ( relatedPostsRecords.length === 0 ) {
                    frontendPreviewElements.push( el( 'p', { key: 'preview-empty', style: { fontStyle: 'italic', color: '#718096' } }, 'Keine passenden Beiträge im System gefunden.' ) );
                } else {
                    var listItems = relatedPostsRecords.map( function( post ) {
                        var titleText = ( post.title && post.title.rendered ) ? post.title.rendered : 'Unbenannter Beitrag';
                        
                        // Datum formatieren, falls eingeschaltet
                        var dateString = '';
						if ( attributes.datum_anzeigen ) {
                            var rawDate = attributes.datum_typ === 'modified' ? post.modified : post.date;
                            if ( rawDate ) {
                                var d = new Date( rawDate );
                                // Erzwingt zweistellige Ausgabe für Tag und Monat
                                var tag   = ( '0' + d.getDate() ).slice( -2 );
                                var monat = ( '0' + ( d.getMonth() + 1 ) ).slice( -2 );
                                var jahr  = d.getFullYear();
                                
                                dateString = tag + '.' + monat + '.' + jahr + ' - ';
                            }
                        }

                        // Listenpunkt-Stil basierend auf dem Umschalter anpassen
                        var liStyle = attributes.punkte_anzeigen === false 
                            ? { listStyleType: 'none', marginBottom: '6px' } 
                            : { marginBottom: '6px' };

                        return el( 'li', { key: 'post-' + post.id, style: liStyle }, 
                            el( 'span', { style: { color: '#4a5568', marginRight: '5px', fontWeight: '5px' } }, dateString ),
                            el( 'span', { 
                                style: { color: '#0051a8', textDecoration: 'underline' },
                                dangerouslySetInnerHTML: { __html: titleText }
                            } )
                        );
                    } );

                    // Wenn Punkte deaktiviert sind, rücken wir das "ul" linksbündig ein
                    var ulStyle = attributes.punkte_anzeigen === false 
                        ? { margin: '0', paddingLeft: '0' } 
                        : { margin: '0', paddingLeft: '20px' };

                    frontendPreviewElements.push( el( 'ul', { key: 'preview-list', style: ulStyle }, listItems ) );
                }

                content.push( el( 'div', { 
                    key: 'frontend-preview-container', 
                    style: { marginTop: '15px', padding: '15px', background: '#ffffff', border: '1px solid #bee3f8', borderRadius: '4px', color: '#333' } 
                }, 
                    el( 'div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.5px', color: '#718096', marginBottom: '10px', fontWeight: 'bold' } }, '👁️ Live Frontend-Vorschau:' ),
                    frontendPreviewElements
                ) );
            }

            var blockProps = useBlockProps( {
                key: 'inline-preview',
                style: boxStyle
            } );

            return [
                el( InspectorControls, { key: 'inline-inspector' },
                    el( PanelBody, { title: 'Block-Einstellungen', initialOpen: true }, sidebarElements )
                ),
                el( 'div', blockProps, content )
            ];
        },
        save: function () { return null; }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data );
