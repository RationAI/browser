//todo delete this file, uniforma access via viewerconfig.js
var user_settings = {
    params: {
        "debug": true
    },
    meta: {},
    data: [],
    background: [],
    shaderSources: [],
    visualizations: [],
    plugins: {}
};


function go(user, newTab, title, image, ...dataArray) {
    loadFormData(user, newTab, title, image, ...dataArray);
    document.getElementById("redirect").submit();
    resetFormData();
}

function goMask(user, newTab, title, image, maskJSONdata, ...dataArray) {
    user_settings.plugins["gui_annotations"] = {};
    loadFormData(user, newTab, title, image, ...dataArray);
    let form = document.getElementById("redirect");

    var node = document.createElement("input");
    node.setAttribute("type", "hidden");
    node.setAttribute("name", `annotation-list`);
    node.setAttribute("id", "annotation-list");

    let annotData = {"version":"5.2.1","objects":[]};
    maskJSONdata = maskData[maskJSONdata];
    for (let annotation of maskJSONdata) {
        annotData.objects.push({
            "type":"rect","left":annotation.coord_x,"top":annotation.coord_y,"width":annotation.tile_w,"height":annotation.tile_w,
            "presetID":1656486783423,"factoryId":"rect","sessionId":1656486783407,"layerId":1656486783423
        });
    }

    node.setAttribute("value", JSON.stringify(annotData));
    form.appendChild(node);node = document.createElement("input");
    node.setAttribute("type", "hidden");
    node.setAttribute("id", "annotation_presets");

    node.setAttribute("name", `annotation_presets`);
    node.setAttribute("value", `[{"color":"#ff0000","factoryID":"rect","presetID":1656486783423,"meta":{"category":{"name":"Category","value":""}}}]`);
    form.appendChild(node);

    form.submit();

    document.getElementById('annotation-list').remove();
    document.getElementById('annotation_presets').remove();
    resetFormData();
}

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

function loadFormData(user, newTab, title, image, ...dataArray) {
    let vis = {name: title, shaders: {}};
    user_settings.data.push(image);

    let microns = undefined;
    const meta = document.getElementById(`${image}-meta`);
    if (meta) {
        microns = Number.parseFloat(meta.dataset.micronsX); //todo what about Y
        if (microns < 0) microns = undefined;
    }

    user_settings.background.push({
        dataReference: 0,
        lossless: false,
        microns: microns
    });

    if (dataArray.length < 1) {
        delete user_settings.visualizations;
    } else {
        let index = 0;
        for (let item of dataArray) {
            item.shader.dataReferences = [user_settings.data.length];
            user_settings.data.push(item.data);
            vis.shaders[index++] = item.shader;
        }
        user_settings.visualizations.push(vis);
    }
    user_settings.meta["session"] = image;

    document.getElementById("visualisation").value = JSON.stringify(user_settings);
    if (newTab) {
        document.getElementById("redirect").setAttribute("target", "_blank");
    } else {
        document.getElementById("redirect").removeAttribute("target");
    }
}

function resetFormData() {
    user_settings = {
        params: {
            "debug": true
        },
        data: [],
        background: [],
        shaderSources: [],
        visualizations: []
    };
}
