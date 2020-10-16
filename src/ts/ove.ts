/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare global {
  interface Window {
    createVectorEditor: any;
  }
}

import genbankToJson from 'bio-parsers/src/parsers/anyToJson';

// DISPLAY Plasmids FILES
export function displayPlasmidViewer(): void {
  const editor: any = {};
  $('.viewer_OVE').each(function() {
    const viewerID = $(this).attr('id');
    $.get($(this).data('href'), function(fileContent) {

      let parsedSequence: any = {};

      genbankToJson(fileContent, function(parsedData) {
        parsedSequence = parsedData[0].parsedSequence;
      });

      const data: any = {};
      const convertToFeaturedDNASequence = function (openVESequence): void {
        data.sequenceData = {
          features: [],
          sequence: openVESequence.sequence
        };
        data.registryData = {
          name: openVESequence.name
        };
        const featureMap = {};

        for (const prop in openVESequence.features) {
          if (!openVESequence.features.hasOwnProperty(prop))
            continue;

          const feature = openVESequence.features[prop];
          const existingFeature = featureMap[feature.id];
          if (existingFeature) {
            existingFeature.locations.push({
              genbankStart: feature.start + 1,
              end: feature.end + 1
            });
          } else {
            featureMap[feature.id] = {
              id: feature.fid,
              type: feature.type,
              name: feature.name,
              forward: feature.forward,
              notes: [{
                name: 'note',
                value: feature.notes
              }],
              start: feature.start,
              end: feature.end
            };
          }
        }
        for (const property in featureMap) {
          if (!featureMap.hasOwnProperty(property))
            continue;
          data.sequenceData.features.push(featureMap[property]);
        }
      };

      const editorProps = {
        editorName: viewerID,
        withPreviewMode: true,
        isFullscreen: false,
        showMenuBar: false,
        withRotateCircularView: false,
        onCopy: function (event, copiedSequenceData, editorState): void {
          // the copiedSequenceData is the subset of the sequence that has been copied in the teselagen sequence format
          const clipboardData = event.clipboardData;
          console.log(editorState.sequenceData);
          clipboardData.setData('text/plain', copiedSequenceData.sequence);
          data.selection = editorState.selectionLayer;
          data.openVECopied = copiedSequenceData;
          convertToFeaturedDNASequence(editorState.sequenceData);
          clipboardData.setData(
            'application/json',
            JSON.stringify(data));

          event.preventDefault();
          // in onPaste in your app you can do:
          // e.clipboardData.getData('application/json')
        },
        PropertiesProps: {
          // the list of tabs shown in the Properties panel
          propertiesList: [
            'general',
            'features',
            'parts',
            'translations',
            'cutsites',
            'orfs',
            'genbank'
          ]
        },
        ToolBarProps: {
          toolList: [
            'saveTool',
            'downloadTool',
            //'importTool',
            //'undoTool',
            //'redoTool',
            'cutsiteTool',
            'featureTool',
            //'alignmentTool',
            //'oligoTool',
            'orfTool',
            'viewTool',
            //'editTool',
            //'findTool',
            //'visibilityTool'
          ]
        },
        StatusBarProps: {
          showCircularity: false,
          showReadOnly: false,
          showAvailability: false,
        },
      };

      editor.viewerID = window.createVectorEditor(document.getElementById(viewerID), editorProps);

      const editorState = {
        // note, sequence data passed here will be coerced to fit the Teselagen data model
        readOnly: true,
        // Open Vector Editor data model
        sequenceData: parsedSequence,
        updateSequenceData: {},
        // clear the sequenceDataHistory if there is any left over from a previous sequence
        sequenceDataHistory: {},
        annotationVisibility: {
          features: true
        },
        panelsShown: [
          [{
            id: 'circular',
            name: 'Plasmid Map',
            active: true
          }, {
            id: 'rail',
            name: 'Linear Map',
            active: false
          }],
          [{
            id: 'sequence',
            name: 'Linear Sequence Map',
            active: true
          }, {
            id: 'properties',
            name: 'Properties',
            active: false
          }]
        ]
      };

      // Change layout for linear sequences
      if (parsedSequence.circular == false) {
        editorState.panelsShown[0][1].active = true;
        editorState.panelsShown[0].shift();
      }

      editor.viewerID.updateEditor(editorState);
    }, 'text');
  });
}
