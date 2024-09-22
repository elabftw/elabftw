//import { StandaloneStructServiceProvider } from 'ketcher-standalone';
import { Editor } from 'ketcher-react'; // Ensure you have this installed
import "ketcher-react/dist/index.css";
import { RemoteStructServiceProvider } from 'ketcher-core';
const structServiceProvider = new RemoteStructServiceProvider(
  '/indigo/v2',
);

//const structServiceProvider = new StandaloneStructServiceProvider();

const KetcherEditor = () => {
  return (
  <div className="ketcher-editor-container">
    <Editor
      staticResourcesUrl={JSON.stringify('/')}
      structServiceProvider={structServiceProvider}
    />
  </div>
  );
};

export default KetcherEditor;
