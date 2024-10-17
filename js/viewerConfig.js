(function(){
    var cookies;
    //https://stackoverflow.com/questions/5639346/what-is-the-shortest-function-for-reading-a-cookie-by-name-in-javascript
    function readCookie(name,c,C,i){
        if(cookies){ return cookies[name]; }

        c = document.cookie.split('; ');
        cookies = {};

        for(i=c.length-1; i>=0; i--){
            C = c[i].split('=');
            cookies[C[0]] = C[1];
        }

        return cookies[name];
    }

    window.readCookie = readCookie;
})();

function fireForm(event, url, question) {
    event.preventDefault();
    if (!confirm(question)) return false;
    const form = document.getElementById('file-browser-form');
    form.action = url;
    form.submit();
}

//direct import of exported HTML session
function openHtmlExport(exported, url) {
    let child = window.open("about:blank","myChild");
    try {
        const {app, data} = JSON.parse(decodeURIComponent(exported));
        let form = `
      <form method="POST" id="redirect" action="${url}">
        <input type="hidden" id="visualisation" name="visualisation">
        <input type="submit" value="">
      </form>
      <script type="text/javascript">
        document.getElementById("visualisation").value = \`${app.replaceAll("\\", "\\\\")}\`;
        const form = document.getElementById("redirect");
        let node;`;

        for (let id in data) {
            form += `node = document.createElement("input");
node.setAttribute("type", "hidden");
node.setAttribute("name", \`${id}\`);
node.setAttribute("value", \`${data[id].replaceAll("\\", "\\\\")}\`);
form.appendChild(node);`;
        }

        form += `
form.submit();
<\/script>`;
        child.document.write(form);
        child.document.close();
    } catch (e) {
        //todo error ...
    }
}


class ViewerConfig {

    constructor(props, interactiveShaderConfigUrl) {
        this.props = props;
        this.props.data = this.props.data || {};
        this.imagePreviewMaker = (file) => {
            //todo support for playin images? now only image server
            // if (typeof file === "string" && file.endsWith(".tif")) {
            //     return this.props.tiffPreviewMaker?.(file);
            // }
            return this.props.tiffPreviewMaker?.(file) || file;
        }
        this.interactiveShaderConfigUrl = interactiveShaderConfigUrl;
        this.visible = false;
        this.hasVisualOutput = false;
        if (this.props.containerId) {
            this.initHtml();
            this.hasVisualOutput = true;
        }

        this._layproto = null;
        this._bgproto = null;

        this.initHiddenForm();

        this.import(this.props.data);

        if (this.hasVisualOutput) {
            var draggedElement = null;
            const self = this;

            document.addEventListener('visibilitychange', (event) =>  {
                document.cookie = `configuration=${self.export()}; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=None; Secure=false; path=/`;
            });

            document.addEventListener('DOMContentLoaded', (event) => {
                function handleDragStart(e) {
                    draggedElement = this;
                    this.style.opacity = '0.4';
                }

                function handleDragEnd(e) {
                    draggedElement = null;
                    this.style.opacity = '1';
                }

                function handleDragOver(e) {
                    e.preventDefault();
                    return false;
                }

                function handleDrop(e) {
                    e.stopPropagation(); // stops the browser from redirecting.
                    return false;
                }
                let items = document.querySelectorAll('.viewer-config-draggable');
                items.forEach(function(item) {
                    item.draggable = true;
                    item.addEventListener('dragstart', handleDragStart);
                    // item.addEventListener('dragover', handleDragOver);
                    item.addEventListener('dragend', handleDragEnd);

                });

                let parent = document.getElementById('viewer-config-shader-setup');
                parent.addEventListener('dragstart', handleDragStart);
                parent.addEventListener('dragenter', (e) => {
                    console.log(e);
                    e.toElement.classList.add('drag-focus');
                });
                parent.addEventListener('dragover', handleDragOver);
                parent.addEventListener('dragleave', (e) => {
                    console.log(e);
                    e.toElement.classList.remove('drag-focus');
                });
                parent.addEventListener('dragend', handleDragEnd);
                parent.addEventListener('drop', (e) => {
                    if (!draggedElement) throw "Invalid dragged node.";

                    let fullPath = draggedElement.dataset.source;
                    if (self.props.data.data.includes(fullPath)) return; //todo message
                    let banner = draggedElement.querySelectorAll('img')[0].src;
                    // let newElem = document.createElement('img');
                    // newElem.classList.add('banner-image', 'layer-skew', 'position-absolute');
                    // newElem.style.top = `${-parent.childNodes.length*25}px`;
                    // newElem.src = banner;
                    // parent.style.height = `${70+parent.childNodes.length*22}px`;
                    // parent.classList.remove('preview');
                    // parent.appendChild(newElem);

                    self._setRenderLayer(fullPath);
                    self._setImportShaderFor(fullPath, 'heatmap');
                });

                let cookie = readCookie('configuration');
                if (cookie) self.import(cookie);
            });
        }
    }

    initHtml() {
        let container = document.getElementById(this.props.containerId);
        if (!container) throw `Container #${this.props.containerId} must exist!`;
        container.style.minWidth = '250px';

        // let bgTissueConfig = this.props.data?.background;
        // bgTissueConfig = bgTissueConfig && bgTissueConfig[0];
        // let imageUrl = bgTissueConfig ? this.props.tiffPreviewMaker(this.props.data.data[bgTissueConfig.dataReference]) : '';

        container.innerHTML = `
<div id="viewer-config-banner" class="position-relative banner-container">
</div>                
<div id="viewer-config-shader-setup" class="preview">
</div>
<div class="d-flex" style="flex-direction: row-reverse; justify-content: space-between">
    <button class="btn pointer" onclick="${this.props.windowName}.clear();">Reset</button> 

    <button class="btn pointer" onclick="${this.props.windowName}.open();">Open</button> 
</div>
        `;
    }

    initHiddenForm() {
        document.body.innerHTML += `<form method="POST" action="${this.props.viewerUrl}"  id="redirect" style="display: none;">
    <input type="hidden" name="visualisation" id="visualisation" value=''>
</form>`;
    }

    checkIsVisible() {
        if (!this.hasVisualOutput) return;
        if (!this.visible) {
            let bgTissueConfig = this.props.data?.background;
            this.bgTissue = bgTissueConfig && bgTissueConfig[0];
            if (this.bgTissue) {
                let container = document.getElementById(this.props.containerId);
                container.classList.remove("d-none");
                container.classList.add("viewer-config-container");

                // container.classList.add("d-flex");
                this.visible = true;
            }
        } else {
            if (!this.props.data?.background) {
                let container = document.getElementById(this.props.containerId);
                container.classList.add("d-none");
                container.classList.remove("viewer-config-container");
                this.visible = false;
            }
        }
    }

    // set custom protocols, hacky: it does not allow resetting protocol since empaia server can handle all, but not iipimage /default/
    bgProto(proto= null) {
        if (proto) {
            this._bgproto = proto;
        }
    }

    layerProto(proto = null) {
        if (proto) {
            this._layproto = proto;
        }
    }

    withSession(referenceFilePath) {
        if (typeof referenceFilePath !== "string" || !referenceFilePath.trim()) {
            //not supported
            this._setMeta("session", null);
            return this;
        }

        this._setMeta("session", referenceFilePath);
        return this;
    }

    _setMeta(key, value) {
        let meta = this.props.data.meta;
        if (!meta) {
            this.props.data.meta = meta = {};
        }
        if (value === undefined || value === null) delete meta[key];
        else meta[key] = value;
        return this;
    }

    open(onFinish=()=>{}) {
        //without user disable session
        const plugins = this.props.data.plugins;
        if (!this.props.data.meta?.["user"] && plugins) {
            console.warn("User not set: session disabled.");
            delete plugins["user-session"];
        }

        if (this.props.importerMetaEndpoint) {
            //fetch additional meta
            const _this = this;
            const url = `${this.props.importerMetaEndpoint}?ajax=imageCoordinatesOffset&tissue=${this._referencedTissue}`;
            fetch(`${this.props.urlRoot}proxy.php?proxy=${encodeURIComponent(url)}`, {
                headers: {
                    'Content-Type': 'application/json',
                }
            }).then(
                data => data.json()
            ).then(
                data => {
                    //todo json parse ugly...
                    _this.setPluginMeta("gui_annotations", JSON.parse(data.payload) || [0, 0], "convertors", "imageCoordinatesOffset");
                    document.getElementById("visualisation").value = _this.export();
                    document.getElementById("redirect").submit();
                    onFinish();
                }
            ).catch(e => {
                //todo error?

                //just submit
                if (confirm("Failed to read WSI metadata - some things (qupath annotations) might not work as expected. Continue?")) {
                    document.getElementById("visualisation").value = _this.export();
                    document.getElementById("redirect").submit();
                    onFinish();
                }
            });

        } else {
            document.getElementById("visualisation").value = this.export();
            document.getElementById("redirect").submit();
            onFinish();

        }
    }

    withNewTab(isNew) {
        if (isNew) {
            document.getElementById("redirect").setAttribute("target", "_blank");
        } else {
            document.getElementById("redirect").removeAttribute("target");
        }
        return this;
    }

    go(user, title, image) {
        //todo user ignored?
        const _oldVisualOutput = this.hasVisualOutput;
        const _oldData = this.props.data;

        let data;
        this.props.data = data = {};
        this.hasVisualOutput = false;
        this.setTissue(image);

        //go does not support shaders -> not used
        delete data.visualizations;

        const _this = this;
        this.open(() => {
            _this.hasVisualOutput = _oldVisualOutput;
            _this.props.data = _oldData;
        });
    }

    setTissue(tissuePath, visual=true) {
        this._setImportTissue(tissuePath);
        if (visual) this._setRenderTissue(tissuePath);
        this.withSession(tissuePath); //todo dirty, and what if multiple files presented -> session stored to one of them :/
        return this;
    }

    setShaderFor(dataPath, shaderType='heatmap') {
        if (this.hasVisualOutput && !this.visible) return;
        if (this._setImportShaderFor(dataPath, shaderType)) {
            this._setRenderLayer(dataPath);
        }
        return this;
    }

    setPluginMeta(plugin_id, value, ...keys) {
        let plugins = this.props.data.plugins;
        if (!plugins) {
            this.props.data.plugins = plugins = {};
        }

        let p = plugins[plugin_id];
        if (!p) {
            this.props.data.plugins[plugin_id] = p = {};
        }
        function find(ctx, keyList) {
          if (!ctx || keyList.length < 2) {
              if (keyList.length === 1) return ctx;
              return undefined;
          }
          const key = keyList.pop();
          if (!ctx[key]) ctx[key] = {};
          return find(ctx[key], keyList);
        }
        const context = find(p, keys.reverse());
        if (!context) throw "Could not write meta for keys: " + keys.toString();
        if (value === undefined || value === null) {
            delete context[keys[keys.length-1]];
        } else {
            context[keys[keys.length-1]] = value;
        }
        return this;
    }

    _unsetLayer(node) {
        node =  node.parentNode;
        let vis = this.props.data.visualizations;

        if (vis?.length > 0) {
            vis = vis[0];
            let path = node.dataset.path;
            if (path) {
                let layer = vis.shaders[path];
                if (layer && this._removeImageData(path) !== -1) {
                    delete vis.shaders[path];
                    node.remove();
                }
            }
        }
    }

    _ensureVisExists() {
        let vis = this.props.data.visualizations;
        if (!vis) {
            this.props.data.visualizations = vis = [{
                lossless: true,
                shaders: {},
                protocol: this._layproto
            }];
        }
        vis = vis[0];
        return vis;
    }

    _setImportShaderFor(dataPath, shaderType) {
        const vis = this._ensureVisExists();

        if (vis.shaders[dataPath]) {
            if (typeof shaderType === "string") {
                vis.shaders[dataPath].type = shaderType;
            } else {
                vis.shaders[dataPath] = shaderType;
            }
            return false;
        }

        if (typeof shaderType === "string") {
            vis.shaders[dataPath] = {
                type: shaderType,
                dataReferences: [this._insertImageData(dataPath)],
                fixed: false,
                params: {}
            };
        } else {
            shaderType.dataReferences = [this._insertImageData(dataPath)];
            vis.shaders[dataPath] = shaderType;
        }
        return true;
    }

    _insertImageData(dataPath) {
        let dataList = this.props.data.data;
        if (!dataList) {
            this.props.data.data = dataList = [];
        }
        let dataIndex = dataList.indexOf(dataPath);
        if (dataIndex === -1) {
            dataIndex = dataList.length;
            dataList.push(dataPath);
        }
        return dataIndex;
    }

    _removeImageData(dataPath) {
        let dataList = this.props.data.data;
        if (!dataList) {
            this.props.data.data = dataList = [];
        }
        let dataIndex = dataList.indexOf(dataPath);
        if (dataIndex !== -1) {
            dataList.splice(dataIndex, 1);
        }
        return dataIndex;
    }

    _setImportTissue(tissuePath) {
        let microns = undefined;
        const meta = document.getElementById(`${tissuePath}-meta`);
        if (meta) {
            microns = Number.parseFloat(meta.dataset.micronsX); //todo what about Y
            if (microns < 0) microns = undefined;
        }

        this.props.data.background = [{
            dataReference: this._insertImageData(tissuePath),
            lossless: false,
            microns: microns,
            protocol: this._bgproto
        }];
        this._referencedTissue = tissuePath;
        this.checkIsVisible();
    }

    _setRenderTissue(tissuePath) {
        if (!this.hasVisualOutput) return;

        let filename = tissuePath.split("/");
        filename = filename[filename.length - 1];

        document.getElementById("viewer-config-banner").innerHTML = `
<img id="viewer-config-banner-image" class="banner-image" src="${this.imagePreviewMaker(tissuePath)}">
<div class="width-full position-absolute bottom-0" style="height: 60px; background: background: var(--color-bg-primary);
background: linear-gradient(0deg, var(--color-bg-primary) 0%, transparent 100%);"></div>
<h3 class="position-absolute bottom-0 f3-light mx-3 my-2 no-wrap overflow-hidden">${filename}</h3>
`;
    }

    _openExternalConfigurator(shaderId) {
        const theWindow = window.open(this.interactiveShaderConfigUrl,
                'config', "height=550,width=850"),
            theDoc = theWindow.document;
        //    theScript = document.createElement('script');

        // const selfRef = this.props.windowName;
        // function injected() {
        //     window.opener[`${selfRef}`]
        // }
        // theScript.innerHTML = 'window.onload = ' + injected.toString() + ';';
        // theDoc.body.appendChild(theScript);

        const _this = this;
        theWindow.onload = function () {
            theWindow.runConfigurator(config => {
                _this._recordExternalConfig(shaderId, config);
                window.console.log(config);
                theWindow.close();
            });
        };
    }

    _recordExternalConfig(shaderId, config) {
        //todo unsafe assignments?
        const vis = this._ensureVisExists();
        vis.shaders[shaderId] = config;
        document.getElementById('viewer-config-shader-select-'+shaderId).value = config.type;
    }

    _setRenderLayer(dataPath) {
        if (!this.hasVisualOutput) return;

        let shaderOpts = [
            {type: 'heatmap', title: 'Heatmap'},
            {type: 'bipolar-heatmap', title: 'Bipolar Heatmap'},
            {type: 'edge', title: 'Edge'},
            {type: 'colormap', title: 'Colormap'},
            {type: 'identity', title: 'Identity'},
        ].map(x => `<option name="shader-type" value="${x.type}">${x.title}</option>`);

        let newElem = document.createElement('div');
        newElem.dataset.source = dataPath;
        let filename = dataPath.split("/");
        filename = filename[filename.length - 1];
        newElem.classList.add('banner-container', 'position-relative');
        newElem.dataset.path = dataPath;
        newElem.innerHTML = `
<span class="material-icons position-absolute left-0 pointer top-0" onclick="${this.props.windowName}._unsetLayer(this);">close</span>
<img class="banner-image" src="${this.imagePreviewMaker(dataPath)}">
<h4 class="position-absolute bottom-0 f4-light mx-3 my-2 no-wrap overflow-hidden">${filename}</h4>
<select class="viewer-config-shader-select position-absolute top-4 right-0" id="viewer-config-shader-select-${dataPath}"
onchange="${this.props.windowName}.setShaderFor('${dataPath}', this.value);">${shaderOpts}</select>
<button class="btn btn-sm position-absolute top-0 right-0" onclick="${this.props.windowName}._openExternalConfigurator('${dataPath}')">Configure shader</button>
`;
        document.getElementById('viewer-config-shader-setup').appendChild(newElem);
    }

    import(data) {
        this.props.data = typeof data === "string" ? JSON.parse(data) : data;
        data = this.props.data;
        if (data.background && data.background.length > 0) {
            this._setRenderTissue(data.data[data.background[0].dataReference]);
        }

        //just one available
        if (data.visualizations && data.visualizations[0]?.shaders) {
            const shaderList = data.visualizations[0].shaders;
            for (let shaderKey in shaderList) {
                let index = shaderList[shaderKey].dataReferences[0]; //just one supported
                //just one avaliable
                this._setRenderLayer(data.data[index]);
            }
        }

        this.checkIsVisible();
    }

    clear() {
        this.props.data = {};

        if (this.hasVisualOutput) {
            document.getElementById("viewer-config-banner").innerHTML = '';
            document.getElementById('viewer-config-shader-setup').innerHTML = '';
            this.checkIsVisible();
        }
    }

    export() {
        return JSON.stringify(this.props.data);
    }
}
