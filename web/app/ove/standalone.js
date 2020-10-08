var data = {};
var convertToFeaturedDNASequence = function (openVESequence) {
  data.sequenceData = {
    features: [],
    sequence: openVESequence.sequence
  };
  data.registryData = {
    name: openVESequence.name
  };
  var featureMap = {};

  for (const prop in openVESequence.features) {
    if (!openVESequence.features.hasOwnProperty(prop))
      continue;

    var feature = openVESequence.features[prop];
    var existingFeature = featureMap[feature.id];
    if (existingFeature) {
      existingFeature.locations.push({
        genbankStart: feature.start + 1,
        end: feature.end + 1
      })
    } else {
      featureMap[feature.id] = {
        id: feature.fid,
        type: feature.type,
        name: feature.name,
        forward: feature.forward,
        notes: [{
            name: 'note',
            value: feature.notes
          }
        ],
        //        locations: [{
        //           genbankStart: feature.start + 1,
        //         end: feature.end + 1
        //   }]
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
}

var editorProps = {
  showMenuBar: false,
  isFullscreen: true,
  showReadOnly: true, //default true
  showCircularity: true,
  // onSave: function(event, sequence, editorState, onSuccess) {
  //  console.log('saving');
  // },
  onImport: function (sequence) {
    try {
      console.log('sequence name', sequence.name);
      document.title = sequence.name;
    } catch (err) {
      console.error('Import Error', err);
    }
    return sequence;
  },
  onCopy: function (event, copiedSequenceData, editorState) {
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

  onPaste: function (event, editorState) {
    var clipboardData = event.clipboardData || event.originalEvent.clipboardData;
    var jsonData = clipboardData.getData('application/json');
    if (jsonData) {
      jsonData = JSON.parse(jsonData);
      jsonData = jsonData.openVECopied;
    }
    return jsonData || {
      sequence: clipboardData.getData('text/plain')
    };
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
      //'saveTool',
      //'downloadTool',
      //'importTool',
      //'undoTool',
      //'redoTool',
      //'cutsiteTool',
      //'featureTool',
      //'alignmentTool',
      // 'oligoTool',
      //'orfTool',
      //'viewTool',
      //'editTool',
      //'findTool',
      //'visibilityTool'
    ]
  },
};

const editor = window.createVectorEditor(document.getElementById('ove'), editorProps);

function whenAvailable(name, callback) {
  var interval = 50; // ms
  window.setTimeout(function() {
    if (window[name]) {
      callback(window[name]);
    } else {
      whenAvailable(name, callback);
    }
  }, interval);
}

whenAvailable("OVEsequenceData", function(t) {
  console.log(window.OVEsequenceData[0].parsedSequence);

  var editorState = {
    // note, sequence data passed here will be coerced to fit the Teselagen data model
    readOnly: true,
    // Open Vector Editor data model
    sequenceData: window.OVEsequenceData[0].parsedSequence,
    updateSequenceData: {},
    // clear the sequenceDataHistory if there is any left over from a previous sequence
    sequenceDataHistory: {},
    annotationVisibility: {
      features: true
    },
    panelsShown: [
      [{
          id: 'circular',
          name: 'Plasmid',
          active: true
        }
      ],
      [{
          id: 'sequence',
          name: 'Sequence Map',
          active: true
        }, {
          id: 'rail',
          name: 'Linear Map',
          active: false
        }, {
          id: 'properties',
          name: 'Properties',
          active: false
        }
      ]
    ]
  };
  console.log(editorState);

  editor.updateEditor(editorState);
});