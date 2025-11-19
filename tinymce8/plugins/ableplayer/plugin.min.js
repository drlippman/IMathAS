/**
 * TinyMCE 8 AblePlayer Plugin
 * Inserts accessible video player iframes with captions and descriptions
 */

(function() {
  'use strict';

  tinymce.PluginManager.add('ableplayer', function(editor, url) {
    
    // Helper function to find the AblePlayer iframe and its wrapper
    const findAblePlayerIframe = function() {
      let selectedNode = editor.selection.getNode();
      
      // Check if the selected node is the iframe
      if (selectedNode.nodeName === 'IFRAME' && selectedNode.classList.contains('ableplayeriframe')) {
        return { iframe: selectedNode, wrapper: null };
      } else if (selectedNode.getAttribute('data-mce-p-class') === 'ableplayeriframe') {
        // Check if iframe is a child of the selected node
        let iframe = selectedNode.querySelector && selectedNode.querySelector('iframe.ableplayeriframe');
        if (iframe) {
          return { iframe: iframe, wrapper: selectedNode };
        }
      }
      
      return null;
    };
    
    // Helper function to find an AblePlayer link
    const findAblePlayerLink = function() {
      let selectedNode = editor.selection.getNode();
      
      // Check if the selected node is a link
      if (selectedNode.nodeName === 'A') {
        const href = selectedNode.getAttribute('href') || '';
        if (href.includes('ableplayer.php')) {
          return selectedNode;
        }
      }
      
      // Check if parent is a link
      let parent = selectedNode.parentElement;
      if (parent && parent.nodeName === 'A') {
        const href = parent.getAttribute('href') || '';
        if (href.includes('ableplayer.php')) {
          return parent;
        }
      }
      
      return null;
    };

    const fixVideoId = function (origid) {
      var vidid = origid;
      if (origid.match(/youtube\.com/)) {
        if (origid.indexOf('playlist?list=')>-1) {
          vidid = href.split('list=')[1].split(/[#&]/)[0];
        } else if (origid.match(/\/embed\//)) {
          vidid = origid.split("/embed/")[1].split(/[#&\?]/)[0];
        } else {
          vidid = origid.split('v=')[1].split(/[#&]/)[0];
        }
      } else if (origid.match(/youtu\.be/)) {
        vidid = origid.split('.be/')[1].split(/[#&\?]/)[0];
      }
      if (!vidid.match(/^[a-zA-Z0-9_-]{11}$/)) {
        return '';
      }
      return vidid;
    }
    
    // Register the dialog
    const openDialog = function() {
      let result = findAblePlayerIframe();
      let linkElement = findAblePlayerLink();
      let isAblePlayerIframe = result !== null;
      let isAblePlayerLink = linkElement !== null;
      let selectedIframe = result ? result.iframe : null;
      let wrapperElement = result ? result.wrapper : null;
      
      // Parse existing values if editing
      let existingValues = {
        insertType: 'iframe',
        video: '',
        captions: { value: '' },
        descriptions: { value: '' },
        linkText: 'Video',
        width: '640',
        height: '530'
      };

      if (isAblePlayerIframe) {
        const src = selectedIframe.getAttribute('src') || '';
        const urlParams = new URLSearchParams(src.split('?')[1] || '');
        
        existingValues.insertType = 'iframe';
        existingValues.video = urlParams.get('video') || '';
        existingValues.captions = { value: urlParams.get('captions') || '' };
        existingValues.descriptions = { value: urlParams.get('descriptions') || '' };
        existingValues.width = selectedIframe.getAttribute('width') || '640';
        existingValues.height = selectedIframe.getAttribute('height') || '360';
      } else if (isAblePlayerLink) {
        const href = linkElement.getAttribute('href') || '';
        const urlParams = new URLSearchParams(href.split('?')[1] || '');
        
        existingValues.insertType = 'link';
        existingValues.video = urlParams.get('video') || '';
        existingValues.captions = { value: urlParams.get('captions') || '' };
        existingValues.descriptions = { value: urlParams.get('descriptions') || '' };
        existingValues.linkText = linkElement.textContent || '';
      }

      const dialogConfig = {
        title: 'Insert AblePlayer Video',
        body: {
          type: 'panel',
          items: [
            {
              type: 'input',
              name: 'video',
              label: 'Video ID or URL',
              placeholder: 'e.g., dQw4w9WgXcQ'
            },
            {
              type: 'urlinput',
              name: 'captions',
              label: 'Captions File (.vtt)',
              filetype: 'file',
              placeholder: 'Select or enter captions file URL'
            },
            {
              type: 'urlinput',
              name: 'descriptions',
              label: 'Descriptions File (.vtt)',
              filetype: 'file',
              placeholder: 'Select or enter descriptions file URL'
            },
            {
              type: 'selectbox',
              name: 'insertType',
              label: 'Insert as',
              items: [
                { value: 'iframe', text: 'Embedded iframe' },
                { value: 'link', text: 'Link' }
              ]
            },
            {
              type: 'input',
              name: 'linkText',
              label: 'Link Text',
              placeholder: 'Enter link text'
            },
            {
              type: 'grid',
              columns: 2,
              items: [
                {
                  type: 'input',
                  name: 'width',
                  label: 'Iframe Width',
                  placeholder: '640'
                },
                {
                  type: 'input',
                  name: 'height',
                  label: 'Iframe Height',
                  placeholder: '530'
                }
              ]
            }
          ]
        },
        buttons: [
          {
            type: 'cancel',
            text: 'Cancel'
          },
          {
            type: 'submit',
            text: 'Insert',
            primary: true
          }
        ],
        initialData: existingValues,
        onChange: function(api, details) {
          // Show/hide fields based on insertType
          if (details.name === 'insertType') {
            const data = api.getData();
            const isLink = data.insertType === 'link';
            
            // Disable/enable fields based on type
            api.setEnabled('linkText', isLink);
            api.setEnabled('width', !isLink);
            api.setEnabled('height', !isLink);
          }
        },
        onSubmit: function(api) {
          const data = api.getData();
          
          // Build the src URL - extract value from urlinput objects
          const params = new URLSearchParams();
          if (data.video) params.append('video', data.video); //fixVideoId(data.video)
          if (data.captions && data.captions.value) params.append('captions', data.captions.value);
          if (data.descriptions && data.descriptions.value) params.append('descriptions', data.descriptions.value);
          
          const src = imasroot+'/course/ableplayer.php?' + params.toString();
          
          let html;
          if (data.insertType === 'link') {
            // Create a link
            const linkText = data.linkText || 'View Video';
            html = `<a href="${src}" target="_blank">${linkText}</a>`;
          } else {
            // Create an iframe
            const width = data.width || '640';
            const height = data.height || '530';
            html = `<iframe class="ableplayeriframe" src="${src}" width="${width}" height="${height}" frameborder="0" allowfullscreen></iframe>`;
          }
          
          // Insert or replace
          if (isAblePlayerIframe || isAblePlayerLink) {
            editor.selection.expand();
          } 
          editor.insertContent(html);
          
          api.close();
        }
      };
      
      // Set initial enabled/disabled state
      const dialog = editor.windowManager.open(dialogConfig);
      const isLink = existingValues.insertType === 'link';
      dialog.setEnabled('linkText', isLink);
      dialog.setEnabled('width', !isLink);
      dialog.setEnabled('height', !isLink);
      
      return dialog;
    };

    // Register the toolbar button
    editor.ui.registry.addButton('ableplayer', {
      text: 'AblePlayer',
      icon: 'embed',
      tooltip: 'Insert AblePlayer Video',
      onAction: function() {
        openDialog();
      }
    });

    // Register the menu item
    editor.ui.registry.addMenuItem('ableplayer', {
      text: 'AblePlayer Video',
      icon: 'embed',
      onAction: function() {
        openDialog();
      }
    });

    // Add context menu for editing existing iframes
    editor.ui.registry.addContextMenu('ableplayer', {
      update: function(element) {
        let iframe = null;
        let link = null;
        
        // Check if element is the iframe
        if (element.nodeName === 'IFRAME' && element.classList.contains('ableplayeriframe')) {
          iframe = element;
        } else if (element.querySelector) {
          // Check if iframe is a child
          iframe = element.querySelector('iframe.ableplayeriframe');
        }
        
        // Check if element is an AblePlayer link
        if (element.nodeName === 'A') {
          const href = element.getAttribute('href') || '';
          if (href.includes('ableplayer.php')) {
            link = element;
          }
        }
        
        if (iframe || link) {
          return [{
            text: 'Edit AblePlayer',
            icon: 'edit-block',
            onAction: function() {
              openDialog();
            }
          }];
        }
        return [];
      }
    });

    return {
      getMetadata: function() {
        return {
          name: 'AblePlayer Plugin',
          url: 'https://ableplayer.github.io/ableplayer/'
        };
      }
    };
  });
})();