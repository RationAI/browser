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
        this.plainImageProtocol = `({type:'image',url:data,buildPyramid:false})`;

        this.props = props;
        this._dataCountMap = {};
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

        this.initHiddenForm();

        this.import(this.props.data);

        if (this.hasVisualOutput) {
            var draggedElement = null;
            const self = this;

            document.addEventListener('visibilitychange', (event) =>  {
                document.cookie = `configuration=${self.export()}; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=None; Secure=false; path=/`;
            });

            document.addEventListener('DOMContentLoaded', (event) => {
                // function handleDragStart(e) {
                //     draggedElement = this;
                //     this.style.opacity = '0.4';
                // }
                //
                // function handleDragEnd(e) {
                //     draggedElement = null;
                //     this.style.opacity = '1';
                // }
                //
                // function handleDragOver(e) {
                //     e.preventDefault();
                //     return false;
                // }
                //
                // function handleDrop(e) {
                //     e.stopPropagation(); // stops the browser from redirecting.
                //     return false;
                // }
                // let items = document.querySelectorAll('.viewer-config-draggable');
                // items.forEach(function(item) {
                //     item.draggable = true;
                //     item.addEventListener('dragstart', handleDragStart);
                //     // item.addEventListener('dragover', handleDragOver);
                //     item.addEventListener('dragend', handleDragEnd);
                //
                // });
                //
                // let parent = document.getElementById('viewer-config-shader-setup');
                // parent.addEventListener('dragstart', handleDragStart);
                // parent.addEventListener('dragenter', (e) => {
                //     console.log(e);
                //     e.toElement.classList.add('drag-focus');
                // });
                // parent.addEventListener('dragover', handleDragOver);
                // parent.addEventListener('dragleave', (e) => {
                //     console.log(e);
                //     e.toElement.classList.remove('drag-focus');
                // });
                // parent.addEventListener('dragend', handleDragEnd);
                // parent.addEventListener('drop', (e) => {
                //     if (!draggedElement) throw "Invalid dragged node.";
                //
                //     let fullPath = draggedElement.dataset.source;
                //     if (self.props.data.data.includes(fullPath)) return; //todo message
                //     let banner = draggedElement.querySelectorAll('img')[0].src;
                //     // let newElem = document.createElement('img');
                //     // newElem.classList.add('banner-image', 'layer-skew', 'position-absolute');
                //     // newElem.style.top = `${-parent.childNodes.length*25}px`;
                //     // newElem.src = banner;
                //     // parent.style.height = `${70+parent.childNodes.length*22}px`;
                //     // parent.classList.remove('preview');
                //     // parent.appendChild(newElem);
                //
                //     const key = self._addLayerToVis(fullPath, 'heatmap');
                //     self._addLayerToDOM(key, fullPath);
                // });

                let cookie = readCookie('configuration');
                if (cookie) self.import(cookie);
            });
        }
    }

    initHtml() {
        let container = document.getElementById(this.props.containerId);
        if (!container) throw `Container #${this.props.containerId} must exist!`;
        container.style.width = '250px';

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

        //todo ugly...
        const setQuPathPresets = () => {
            this.setPluginMeta("gui_annotations", [{"color":"#b4b4b4","factoryID":"polygon","presetID":
                    "Ignore*","meta":{"category":{"name":"Category","value":"Ignore*"}}},{"color":"#c80000","factoryID":"polygon",
                "presetID":"Tumor","meta":{"category":{"name":"Category","value":"Tumor"}}},{"color":"#96c896","factoryID":"polygon",
                "presetID":"Stroma","meta":{"category":{"name":"Category","value":"Stroma"}}},{"color":"#a05aa0","factoryID":"polygon",
                "presetID":"Immune cells","meta":{"category":{"name":"Category","value":"Immune cells"}}},{"color":"#323232",
                "factoryID":"polygon","presetID":"Necrosis","meta":{"category":{"name":"Category","value":"Necrosis"}}},{"color":
                    "#0000b4","factoryID":"polygon","presetID":"Region*","meta":{"category": {"name":"Category","value":"Region*"}}},
                {"color":"#fa3e3e","factoryID":"polygon","presetID":"Positive","meta":{"category":{"name":"Category","value":"Positive"
                        }}},{"color":"#7070e1","factoryID":"polygon","presetID":"Negative","meta":{"category":{"name":"Category","value":
                                "Negative"}}}], "staticPresets");
            this.setPluginMeta("gui_annotations", false, "enablePresetModify");
        };

        if (this.props.importerMetaEndpoint && this._referencedTissue.includes(".mrxs")) {
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
                    setQuPathPresets();
                    document.getElementById("visualisation").value = _this.export();
                    document.getElementById("redirect").submit();
                    onFinish();
                }
            ).catch(e => {
                //todo error?

                //just submit
                if (confirm("Failed to read WSI metadata - some things (qupath annotations) might not work as expected. Continue?")) {
                    setQuPathPresets();
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

    go(user, title, image, ...dataArray) {
        this._goInit();
        this.setPlainWSI(image);
        this._goFinish(user, title, ...dataArray);
    }

    goPlain(user, title, image, ...dataArray) {
        this._goInit();
        this.setPlainImage(image);
        this._goFinish(user, title, ...dataArray);
    }

    _goInit() {
        this._oldVisualOutput = this.hasVisualOutput;
        this._oldData = this.props.data;

        this.props.data = {};
        this.hasVisualOutput = false;
    }

    _goFinish(user, title, ...dataArray) {
        //todo user ignored?
        let data = this.props.data;
        //todo reuse?
        if (dataArray.length < 1) {
            delete data.visualizations;
        } else {
            let index = 0,
                vis = {
                    lossless: true,
                    shaders: {}
                };
            for (let item of dataArray) {
                item.shader.dataReferences = [data.data.length];
                data.data.push(item.data);
                vis.shaders[index++] = item.shader;
            }
            data.visualizations.push(vis);
        }

        const _this = this;
        this.open(() => {
            _this.hasVisualOutput = _this._oldVisualOutput;
            delete _this._oldVisualOutput;
            _this.props.data = _this._oldData;
            delete _this._oldData;
        });
    }

    setPlainWSI(tissuePath, visual=true) {
        this._setImportTissue(tissuePath);
        if (visual && this.hasVisualOutput) this._setRenderTissue(tissuePath);
        this.withSession(tissuePath); //todo dirty, and what if multiple files presented -> session stored to one of them :/
        return this;
    }

    setPlainImage(url, visual=true) {
        //todo problem if in safe mode, does not work :/
        this._setImportTissue(url);
        //change protocol -> plain image object config
        this.props.data.background[0].protocol = this.plainImageProtocol;
        if (visual && this.hasVisualOutput) {
            this._setRenderPlainImage(url);
        }
        this.withSession(url);
        return this;
    }

    setShaderFor(dataPath, shaderType='heatmap') {
        if (this.hasVisualOutput && !this.visible) return;
        if (!this.checkCanInsertWSIImageLayer()) return;

        const vis = this._ensureVisExists();
        delete vis.protocol;
        const key = this._addLayerToVis(dataPath, shaderType);
        this._addLayerToDOM(key, dataPath);
        return this;
    }

    setPlainImageShaderFor(dataPath, shaderType='heatmap') {
        if (this.hasVisualOutput && !this.visible) return;
        if (!this.checkCanInsertPlainImageLayer()) return;

        const vis = this._ensureVisExists();
        vis.protocol = this.plainImageProtocol;
        const key = this._addLayerToVis(dataPath, shaderType);
        this._addLayerToDOM(key, dataPath);
        return this;
    }

    get isPlainImageBackground() {
        //we render only single background here
        return !! this.props.data.background?.[0]?.protocol;
    }

    get isPlainImageOverlay() {
        const vis = this._ensureVisExists();
        return !! vis.protocol;
    };

    checkCanInsertPlainImageLayer() {
        return this._checkCanInsertAndRemove("Plain images can be only inserted as single layer. Remove existing layers?")
    }

    checkCanInsertWSIImageLayer() {
        if (!this.isPlainImageOverlay) return true;
        return this._checkCanInsertAndRemove("Plain images are incompatible with pyramidal images. Remove existing image layer?")
    }

    _checkCanInsertAndRemove(message) {
        const vis = this._ensureVisExists();
        const layers = Object.keys(vis.shaders);
        if (layers.length > 0) {
            if (confirm(message)) {
                layers.forEach(l => this._unsetLayer(l));
                document.getElementById('viewer-config-shader-setup').innerHTML = '';
                return true;
            }
            return false;
        }
        return true;
    }

    changeLayerConfigFor(uid, shaderType) {
        const vis = this._ensureVisExists();

        let shaderObject = vis.shaders[uid];
        if (shaderObject) {
            if (typeof shaderType === "string") {
                shaderObject.type = shaderType;
            } else {
                shaderType.dataReferences = shaderObject.dataReferences;
                vis.shaders[uid] = shaderType;
            }
        } else {
            throw "Invalid change of params for non-existing layer shader!";
        }
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

    _unsetLayer(uid) {
        let vis = this.props.data.visualizations;

        if (vis?.length > 0) {
            vis = vis[0]; //just one possible
            let layer = vis.shaders[uid];
            if (layer && this._removeImageData(uid)) {
                delete vis.shaders[uid];
                return true;
            }
        }
        return false;
    }

    _ensureVisExists() {
        let vis = this.props.data.visualizations;
        if (!vis) {
            this.props.data.visualizations = vis = [{
                lossless: true,
                shaders: {}
            }];
        }
        vis = vis[0];
        return vis;
    }

    _addLayerToVis(dataPath, shaderType) {
        const vis = this._ensureVisExists();

        let shaderKey = dataPath,
            zeroShaderObject = vis.shaders[shaderKey];
        if (zeroShaderObject) {
            if (zeroShaderObject._browserCount === undefined) {
                zeroShaderObject._browserCount = 1;
            }
            shaderKey += "-" + zeroShaderObject._browserCount++;
        }

        if (typeof shaderType === "string") {
            vis.shaders[shaderKey] = {
                type: shaderType,
                dataReferences: [this._insertImageData(dataPath)],
                fixed: false,
                params: {}
            };
        } else {
            shaderType.dataReferences = [this._insertImageData(dataPath)];
            vis.shaders[shaderKey] = shaderType;
        }
        return shaderKey;
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
            this._dataCountMap[dataPath] = 1;
        } else {
            this._dataCountMap[dataPath]++;
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
            this._dataCountMap[dataPath]--;
            if (this._dataCountMap[dataPath] < 1) {
                dataList.splice(dataIndex, 1);
                delete this._dataCountMap[dataPath];
            }
            return true;
        }
        return false;
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
            microns: microns
        }];
        this._referencedTissue = tissuePath;
        this.checkIsVisible();
    }

    _setRenderTissue(tissuePath) {
        if (!this.hasVisualOutput) return;
        let filename = tissuePath.split("/");
        filename = filename[filename.length - 1];
        this._setRenderBackground(filename, this.imagePreviewMaker(tissuePath));
    }

    _setRenderPlainImage(imageRelPath) {
        let filename = imageRelPath.split("/");
        filename = filename[filename.length - 1];
        this._setRenderBackground(filename, imageRelPath);
    }

    _setRenderBackground(name, url) {
        document.getElementById("viewer-config-banner").innerHTML = `
<img id="viewer-config-banner-image" class="banner-image" src="${url}">
<div class="width-full position-absolute bottom-0" style="height: 60px; background: background: var(--color-bg-primary);
background: linear-gradient(0deg, var(--color-bg-primary) 0%, transparent 100%);"></div>
<h3 class="position-absolute bottom-0 f3-light mx-3 my-2 no-wrap overflow-hidden">${name}</h3>
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
        const shader = vis.shaders[shaderId];
        if (shader) {
            config.dataReferences = shader.dataReferences;
        }
        vis.shaders[shaderId] = config;
        document.getElementById('viewer-config-shader-select-'+shaderId).value = config.type;
    }

    _addLayerToDOM(uid, dataPath, isPlainImage) {
        if (!this.hasVisualOutput) return;

        let shaderOpts = [
            {type: 'heatmap', title: 'Heatmap'},
            {type: 'bipolar-heatmap', title: 'Bipolar Heatmap'},
            {type: 'edge', title: 'Edge'},
            {type: 'colormap', title: 'Colormap'},
            {type: 'identity', title: 'Identity'},
        ].map(x => `<option name="shader-type" value="${x.type}">${x.title}</option>`);

        let imageUrl = this.isPlainImageOverlay ? dataPath : this.imagePreviewMaker(dataPath);
        let newElem = document.createElement('div');
        newElem.dataset.source = dataPath;
        let filename = dataPath.split("/");
        filename = filename[filename.length - 1];
        newElem.classList.add('banner-container', 'position-relative');
        newElem.dataset.path = dataPath;
        newElem.innerHTML = `
<span class="material-icons position-absolute left-0 pointer top-0" onclick="${this.props.windowName}._unsetLayer(this.parentElement.dataset.path) && this.remove();">close</span>
<img class="banner-image" src="${imageUrl}">
<h4 class="position-absolute bottom-0 f4-light mx-3 my-2 no-wrap overflow-hidden">${filename}</h4>
<select class="viewer-config-shader-select position-absolute top-4 right-0" id="viewer-config-shader-select-${uid}"
onchange="${this.props.windowName}.changeLayerConfigFor('${uid}', this.value);">${shaderOpts}</select>
<button class="btn btn-sm position-absolute top-0 right-0" onclick="${this.props.windowName}._openExternalConfigurator('${uid}')">Configure shader</button>
`;
        document.getElementById('viewer-config-shader-setup').appendChild(newElem);
    }

    import(data) {
        this.props.data = typeof data === "string" ? JSON.parse(data) : data;
        data = this.props.data;
        if (data.background && data.background.length > 0) {
            this._referencedTissue = data.data[data.background[0].dataReference];
            if (this.isPlainImageBackground) {
                this._setRenderPlainImage(this._referencedTissue);
            } else {
                this._setRenderTissue(this._referencedTissue);
            }
        }

        //just one available
        if (data.visualizations && data.visualizations[0]?.shaders) {
            const shaderList = data.visualizations[0].shaders;
            for (let shaderKey in shaderList) {
                let index = shaderList[shaderKey].dataReferences[0]; //just one supported
                //just one avaliable
                this._addLayerToDOM(shaderKey, data.data[index]);
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
