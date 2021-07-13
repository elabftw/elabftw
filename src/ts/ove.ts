/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten
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

import anyToJson from 'bio-parsers/es/parsers/anyToJson';
import { notif } from './misc';

// DISPLAY Plasmids FILES
export function displayPlasmidViewer(): void {
  const editor: any = {};
  $('.viewer-ove').each(function() {
    const viewerID = $(this).attr('id');
    const filename = $(this).data('href');
    const realName = $(this).data('realName');

    // helper function to convert Blob to File
    function blobToFile(theBlob: Blob, fileName: string): File {
      //A Blob() is almost a File() - it's just missing the two properties below which we will add
      return new File([theBlob], fileName, { lastModified: new Date().getTime(), type: theBlob.type });
    }

    async function parseFile(fileContent) {
      const parsedData = await anyToJson(fileContent, {
        fileName: realName,
        guessIfProtein: true
      });
      // we always return an array of results because some files my contain multiple sequences
      // parsedData[0].success //either true or false
      // parsedData[0].messages //either an array of strings giving any warnings or errors generated during the parsing process
      
      // Test if fileContent was parsed successfully. if false: show notification
      if (parsedData.length === 0) {
        console.log('Problem with file: ' + realName);
        return;
      }

      if (parsedData[0].success === false) {
        notif({res: false, msg: 'Invalid DNA data in file ' + realName});
        return;
      }

      if (parsedData[0].messages.length !== 0) {
        console.log('File: ' + realName + '; ' + parsedData[0].messages[0]);
        return;
      }

      const parsedSequence = parsedData[0].parsedSequence;

      const data: any = {};
      const convertToFeaturedDNASequence = function(openVESequence): void {
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
        showGCContentByDefault: true,
        onCopy: function(event, copiedSequenceData, editorState): void {
          // the copiedSequenceData is the subset of the sequence that has been copied in the teselagen sequence format
          const clipboardData = event.clipboardData;
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
            //'viewTool',
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

      // exchange 'Open Editor' Button text to 'Open Viewer'
      const oveButton = document.getElementById(viewerID).firstChild.firstChild.firstChild.firstChild.firstChild.firstChild as HTMLElement;
      oveButton.innerHTML = 'Open Viewer';
    }

    // load DNA data either as File (.dna files Snapgene) or as String
    if (filename.slice(-4) === '.dna') {
      const xhr = new XMLHttpRequest();
      xhr.open('GET', filename, true);
      xhr.responseType = 'blob';
      xhr.onload = function(): void {
        if (this.status == 200) {
          parseFile(blobToFile(this.response, realName));
        }
      };
      xhr.send();
    } else {
      $.get(filename, function(fileContent) {
        parseFile(fileContent);
      }, 'text');
    }
  });
}
