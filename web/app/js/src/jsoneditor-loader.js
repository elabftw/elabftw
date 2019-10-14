// create the editor
const container = document.getElementById("jsoneditor")
const options = {}
const editor = new JSONEditor(container, options)

// set json
const initialJson = {
    "Template Data For Experiments":
    {
      "Title":"Title",
      "ExpName":"ExpName"
    },
    "Array": [1, 2, 3],
    "Boolean": true,
    "Null": null,
    "Number": 123,
    "Test ": {"a": "b", "c": "d"},
    "String": "Hello World"
}
editor.set(initialJson)

// get json
const updatedJson = editor.get()
