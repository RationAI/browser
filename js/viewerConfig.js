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

class ViewerConfig {

    constructor(props) {
        this.props = props;
        this.props.data = this.props.data || {};
        this.imagePreviewMaker = (file) => {
            if (typeof file === "string" && file.endsWith(".tif")) { //todo ending
                return this.props.tiffPreviewMaker?.(file);
            }
            return file;
        }
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

    withUser(user) {
        let meta = this.props.data.meta;
        if (!meta) {
            this.props.data.meta = meta = {};
        }

        if (!user) {
            delete meta["user"];
        } else {
            meta["user"] = user;
        }
        return this;
    }

    withSession(referenceFilePath) {
        if (typeof referenceFilePath !== "string" || !referenceFilePath.trim()) {
            //not supported
            delete plugins["user-session"];
            return this;
        }

        let plugins = this.props.data.plugins;
        if (!plugins) {
            this.props.data.plugins = plugins = {};
        }
        plugins["user-session"] = {
            referenceFile: referenceFilePath,
            permaLoad: true,
        };
        return this;
    }

    open() {
        //without user disable session
        if (!this.props.data.meta?.["user"]) {
            console.warn("User not set: session disabled.");
            delete this.props.data.plugins["user-session"];
        }

        document.getElementById("visualisation").value = this.export();
        document.getElementById("redirect").submit();
    }

    setTissue(tissuePath) {
        this._setImportTissue(tissuePath);
        this._setRenderTissue(tissuePath);
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

    _setImportShaderFor(dataPath, shaderType) {
        let vis = this.props.data.visualizations;
        if (!vis) {
            this.props.data.visualizations = vis = [{
                lossless: true,
                shaders: {}
            }];
        }
        vis = vis[0];

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
        this.props.data.background = [{
            dataReference: this._insertImageData(tissuePath),
            "lossless": false
        }];
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
<select class="viewer-config-shader-select" style="    position: absolute;
    top: 0;
    right: 0;"
onchange="${this.props.windowName}.setShaderFor('${dataPath}', this.value);">${shaderOpts}</select>
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
