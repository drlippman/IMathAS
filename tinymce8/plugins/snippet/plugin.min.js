/**
 * TinyMCE 8 Snippet Plugin
 * Allows insertion of prewritten snippets with grouped organization
 */

(function() {
  'use strict';

  const PluginManager = tinymce.PluginManager;

  // Helper functions
  const createSnippetList = (snippetList, callback) => {
    return () => {

      if (typeof snippetList === "function") {
        snippetList(callback);
        return;
      }

      if (typeof snippetList === "string") {
        fetch(snippetList)
          .then(response => response.json())
          .then(data => callback(data))
          .catch(error => {
            console.error('Error loading snippets:', error);
            callback([]);
          });
      } else {
        callback(snippetList || []);
      }
    };
  };

  const insertSnippet = (editor, html) => {
    if (html) {
      editor.insertContent(html);
      editor.focus();
    }
  };

  const createPreviewHtml = (content) => {
    if (!content) return '<p><em>No content to preview</em></p>';
    return content;
  };

  // Main plugin function
  PluginManager.add('snippet', function(editor) {
    let currentSnippetHtml = '';

    const showDialog = (snippetList) => {
      if (!snippetList || snippetList.length === 0) {
        const imasroot = window.imasroot || '';
        const message = editor.translate('No Snippets defined.') + 
          '<br><a href="' + imasroot + '/course/editsnippets.php" target="_blank">' +
          editor.translate('Create some') + '</a>';
        
        editor.notificationManager.open({ 
          text: message, 
          type: 'info' 
        });
        return;
      }

      // Prepare group options
      const groupOptions = snippetList.map(group => ({
        text: group.text,
        value: group.text
      }));

      // Prepare initial snippet options from first group
      const initialSnippetOptions = snippetList[0].items.map(item => ({
        text: item.text,
        value: item.text
      }));

      // Set initial snippet content
      currentSnippetHtml = snippetList[0].items[0]?.content || '';
      const previewHtml = createPreviewHtml(currentSnippetHtml);

      const dialogConfig = {
        title: 'Insert Prewritten Snippet',
        size: 'large',
        body: {
          type: 'panel',
          items: [
            {
              type: 'selectbox',
              name: 'snippetGroup',
              label: 'Group',
              items: groupOptions
            },
            {
              type: 'selectbox',
              name: 'snippet',
              label: 'Snippets', 
              items: initialSnippetOptions
            },
            {
              type: 'iframe',
              name: 'preview',
              label: 'Preview',
              sandboxed: false,
              border: true,
              flex: 1
            }
          ]
        },
        buttons: [
          {
            type: 'custom',
            name: 'addEdit',
            text: 'Add / Edit Snippets',
            primary: false
          },
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
        initialData: {
          snippetGroup: groupOptions[0]?.value || '',
          snippet: initialSnippetOptions[0]?.value || '',
          preview: previewHtml
        },
        onChange: (api, details) => {
          const data = api.getData();
          
          if (details.name === 'snippetGroup') {
            // Find the selected group
            const selectedGroup = snippetList.find(group => group.text === data.snippetGroup);
            if (selectedGroup) {
              const newSnippetOptions = selectedGroup.items.map(item => ({
                text: item.text,
                value: item.text
              }));
              // Update snippet dropdown
              currentSnippetHtml = selectedGroup.items[0]?.content || '';

              dialogConfig.body.items[1].items = newSnippetOptions;
              dialogConfig.initialData.snippetGroup = selectedGroup.text;
              dialogConfig.initialData.snippet = newSnippetOptions[0]?.value || '';
              dialogConfig.initialData.preview = createPreviewHtml(currentSnippetHtml);
              api.redial(dialogConfig);
              
            }
          } else if (details.name === 'snippet') {
            // Find the selected snippet
            const selectedGroup = snippetList.find(group => group.text === data.snippetGroup);
            if (selectedGroup) {
              const selectedSnippet = selectedGroup.items.find(item => item.text === data.snippet);
              if (selectedSnippet) {
                currentSnippetHtml = selectedSnippet.content || '';
                
                // Update preview
                const previewHtml = createPreviewHtml(currentSnippetHtml);
                api.setData({preview: previewHtml});
              }
            }
          }
        },
        onAction: (api, details) => {
          if (details.name === 'addEdit') {
            const imasroot = window.imasroot || '';
            window.open(imasroot + '/course/editsnippets.php', '_blank');
            api.close();
          }
        },
        onSubmit: (api) => {
          insertSnippet(editor, currentSnippetHtml);
          api.close();
        }
      };

      const dialog = editor.windowManager.open(dialogConfig);
      
    };
    
    const snippetList = editor.getParam('snippet_list');
    
    // Only add UI elements if snippets are configured
    if (typeof snippetList !== 'undefined' && snippetList !== false) {
      
      editor.ui.registry.addIcon('snippet', '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" fill="none" stroke="black"/><rect x="6" y="6" width="12" height="4" fill="none" stroke="black" stroke-dasharray="2 1.5"/><rect x="6" y="14" width="12" height="4" fill="none" stroke="black" stroke-dasharray="2 1.5"/></svg>');

      // Register command
      editor.addCommand('mceSnippet', (ui, value) => {
        createSnippetList(snippetList, showDialog)
      });

      // Add toolbar button
      editor.ui.registry.addButton('snippet', {
        tooltip: 'Insert Prewritten Snippet',
        icon: 'snippet',
        onAction: createSnippetList(snippetList, showDialog)
      });

      // Add menu item
      editor.ui.registry.addMenuItem('snippet', {
        text: 'Prewritten Snippet',
        icon: 'snippet',
        onAction: createSnippetList(snippetList, showDialog)
      });
    }

    // Return public API (optional)
    return {
      getMetadata: () => ({
        name: 'Snippet Plugin',
        url: 'https://github.com/your-repo/tinymce-snippet-plugin'
      })
    };
  });

})();