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
export function displayPlasmidViewer(about: DOMStringMap): void {
  const editor: any = {};
  Array.from(document.getElementsByClassName('viewer-ove')).forEach(el => {
    const oveDivDataset = (el as HTMLDivElement).dataset;
    const viewerID = el.id;
    const filename = oveDivDataset.href;
    const realName = oveDivDataset.realName;

    // A Blob() is almost a File(): it's just missing two properties (lastModified and a name)
    // we also add the optional (mime) type attribute
    function blobToFile(theBlob: Blob, fileName: string): File {
      return new File([theBlob], fileName, { lastModified: new Date().getTime(), type: theBlob.type });
    }

    // Save a PNG image of the current plasmid map as a new attachment
    function savePlasmidMapAsImage(opts): void {
      const reader = new FileReader();
      reader.readAsDataURL(opts.pngFile);
      reader.onloadend = function(): void {
        $.post('app/controllers/EntityAjaxController.php', {
          saveAsImage: true,
          realName: realName,
          content: reader.result, // the png as data url
          id: about.id,
          type: about.type,
        }).done(function(json) {
          notif(json);
        });
      };
    };

    async function parseFile(fileContent): Promise<void> {
      const parsedData = await anyToJson(fileContent, {
        fileName: realName,
        guessIfProtein: true,
      });
      // we always return an array of results because some files my contain multiple sequences
      // parsedData[0].success //either true or false
      // parsedData[0].messages //either an array of strings giving any warnings or errors generated during the parsing process
      // Test if fileContent was parsed successfully. if false: show notification
      if (parsedData.length === 0) {
        console.error('Problem with file: ' + realName);
        return;
      }

      if (parsedData[0].success === false) {
        notif({res: false, msg: 'Invalid DNA data in file ' + realName});
        return;
      }

      if (parsedData[0].messages.length !== 0) {
        console.error('File: ' + realName + '; ' + parsedData[0].messages[0]);
        return;
      }

      const parsedSequence = parsedData[0].parsedSequence;

      const data: any = {};
      const convertToFeaturedDNASequence = function(openVESequence): void {
        data.sequenceData = {
          features: [],
          sequence: openVESequence.sequence,
        };
        data.registryData = {
          name: openVESequence.name,
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
              end: feature.end + 1,
            });
          } else {
            featureMap[feature.id] = {
              id: feature.fid,
              type: feature.type,
              name: feature.name,
              forward: feature.forward,
              notes: [{
                name: 'note',
                value: feature.notes,
              }],
              start: feature.start,
              end: feature.end,
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
        showReadOnly: false,
        showGCContentByDefault: true,
        alwaysAllowSave: true,
        generatePng: true,
        handleFullscreenClose: function(): void { // event could be used as parameter
          editor[viewerID].close();
          $('#filesdiv').load('?mode=' + about.page + '&id=' + about.id + ' #filesdiv > *');
        },
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
        // repurposed to save an image as new attachment of the current plasmid map
        onSave: function(opts = {} as any): void { // , sequenceDataToSave, editorState, onSuccessCallback could be used as parameter
          savePlasmidMapAsImage(opts);
        },
        PropertiesProps: {
          // the list of tabs shown in the Properties panel
          propertiesList: [
            'general',
            'features',
            'parts',
            'primers',
            'translations',
            'cutsites',
            'orfs',
            'genbank',
          ],
        },
        ToolBarProps: {
          toolList: [
            {
              name: 'saveTool',
              tooltip: 'Save image of plasmid map',
            },
            'downloadTool',
            //'importTool',
            //'undoTool',
            //'redoTool',
            'cutsiteTool',
            'featureTool',
            'partTool',
            //'alignmentTool',
            'oligoTool', // Primers
            'orfTool',
            //'editTool',
            'findTool',
            //'visibilityTool'
          ],
        },
        StatusBarProps: {
          showCircularity: false,
          showReadOnly: false,
          showAvailability: false,
        },
      };

      editor[viewerID] = window.createVectorEditor(document.getElementById(viewerID), editorProps);

      const editorState = {
        // note, sequence data passed here will be coerced to fit the Teselagen data model
        readOnly: true,
        // Open Vector Editor data model
        sequenceData: parsedSequence,
        updateSequenceData: {},
        // clear the sequenceDataHistory if there is any left over from a previous sequence
        sequenceDataHistory: {},
        annotationVisibility: {
          features: true,
        },
        panelsShown: [
          [{
            id: 'circular',
            name: 'Plasmid Map',
            active: true,
          }, {
            id: 'rail',
            name: 'Linear Map',
            active: false,
          }],
          [{
            id: 'sequence',
            name: 'Linear Sequence Map',
            active: true,
          }, {
            id: 'properties',
            name: 'Properties',
            active: false,
          }],
        ],
      };

      // Change layout for linear sequences
      if (parsedSequence.circular == false) {
        editorState.panelsShown[0][1].active = true;
        editorState.panelsShown[0].shift();
      }

      editor[viewerID].updateEditor(editorState);

      // change button text from 'Open Editor' to 'Open Viewer'
      const oveButton = document.getElementById(viewerID).firstChild.firstChild.firstChild.firstChild.firstChild.firstChild as HTMLElement;
      oveButton.innerText = 'Open Viewer';
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
