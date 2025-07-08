class AccessibleTreeWidget {
    constructor(container, data, options = {}) {
        this.container = container;
        this.data = data;
        this.options = {
            selectionMode: options.selectionMode || 'single', // 'single' or 'multi'
            selectableItems: options.selectableItems || 'all', // 'all', 'children', 'parents'
            showCounts: options.showCounts || false,
            onSelectionChange: options.onSelectionChange || (() => {}),
            onLoadError: options.onLoadError || ((error, item) => console.error('Failed to load children for', item.label, error)),
            ...options
        };
        
        this.selectedItems = new Set();
        this.expandedItems = new Set();
        this.renderedItems = new Map();
        this.focusedItem = null;
        this.loadingItems = new Set(); // Track items currently being loaded
        this.loadedItems = new Set(); // Track items that have been loaded
        
        this.init();
    }

    init() {
        this.container.innerHTML = '';
        this.container.className = 'tree-widget';
        
        this.initializeSelection(this.data);
        if (this.options.showCounts) {
            this.initializeCounts(this.data);
        }

        const tree = document.createElement('ul');
        tree.className = 'tree-list';
        tree.setAttribute('role', 'tree');
        tree.setAttribute('aria-label', 'Tree navigation');
        tree.setAttribute('aria-multiselectable', this.options.selectionMode === 'multi');
        
        this.renderItems(this.data, tree, 1);
        this.container.appendChild(tree);
        
        this.bindEvents();
        this.setInitialFocus();
        this.updateSelectionUI(); // Ensure initial ARIA states are correct
    }

    renderItems(items, parentElement, level) {
        items.forEach(item => {
            const li = this.createTreeItem(item, level);
            parentElement.appendChild(li);
            this.renderedItems.set(item.id, { element: li, data: item });
        });
    }

    createTreeItem(item, level) {
        const li = document.createElement('li');
        li.className = 'tree-item';
        li.setAttribute('role', 'treeitem');
        li.setAttribute('aria-level', level);
        li.setAttribute('data-id', item.id);
        
        const hasChildren = item.children && item.children.length > 0;
        const hasChildrenUrl = item.childrenUrl && !this.loadedItems.has(item.id);
        const canHaveChildren = hasChildren || hasChildrenUrl;
        const isExpanded = this.expandedItems.has(item.id);
        const isSelected = this.selectedItems.has(item.id);
        const isLoading = this.loadingItems.has(item.id);
        
        if (canHaveChildren) {
            li.setAttribute('aria-expanded', isExpanded);
        } else {
            li.setAttribute('aria-expanded', 'false');
        }

        // Set selection attributes based on whether item can be selected
        if (this.canSelectItem(item)) {
            if (this.options.selectionMode === 'multi') {
                li.setAttribute('aria-checked', isSelected);
            } else {
                li.setAttribute('aria-selected', isSelected);
            }
        }

        const content = document.createElement('div');
        content.className = 'tree-item-content';
        content.tabIndex = -1;
        
        if (item.disabled) content.classList.add('disabled');
        if (item.locked) content.classList.add('locked');
        if (isLoading) content.classList.add('loading');

        // Expander button
        const expander = document.createElement('button');
        expander.type = 'button';
        expander.className = 'tree-expander';
        expander.setAttribute('aria-hidden', 'true');
        expander.tabIndex = -1;
        
        if (canHaveChildren) {
            if (isLoading) {
                expander.textContent = '⟳';
                expander.style.animation = 'spin 1s linear infinite';
            } else {
                expander.textContent = isExpanded ? '-' : '+'; //'▼' : '▶';
                expander.style.animation = '';
            }
        } else {
            expander.classList.add('no-children');
        }

        // Selection input
        const selectionContainer = document.createElement('div');
        selectionContainer.className = 'tree-selection';
        
        if (this.canSelectItem(item)) {
            const input = document.createElement('input');
            input.type = this.options.selectionMode === 'single' ? 'radio' : 'checkbox';
            input.name = this.options.selectionMode === 'single' ? 'tree-selection' : '';
            input.value = item.id;
            input.tabIndex = -1;
            input.setAttribute('aria-hidden', 'true');
            input.disabled = item.disabled || item.locked;
            input.checked = isSelected;
            
            selectionContainer.appendChild(input);
        }

        // Label
        const label = document.createElement('span');
        label.className = 'tree-label';
        if (item.hasOwnProperty('userights')) {
            label.classList.add('r' + item.userights);
        }
        label.textContent = item.label;
        if (this.options.showCounts && item.hasOwnProperty('count') && !item.childrenUrl && !item.notselectable) {
            const countdisp = document.createElement('span');
            countdisp.textContent = ' (' + item.count + ')';
            label.append(countdisp);
        }
        if (item.federated) {
            const fedicon = document.createElement('span');
            fedicon.className = "fedico";
            fedicon.title = _('Federated');
            fedicon.innerHTML = '&lrarr;';
            label.append(fedicon);
        }
        
        content.appendChild(expander);
        content.appendChild(selectionContainer);
        content.appendChild(label);
        if (item.links) {
            let linkbtn = '<a tabindex=0 class="dropdown-toggle arrow-down" id="tdd'+item.id+'" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
	        linkbtn += _('Actions')+'</a>';
	        linkbtn += '<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="tdd'+item.id+'">';
            for (let i=0;i<item.links.length;i++) {
                linkbtn += '<li><a href="'+item.links[i].href+'"';
                if (item.links[i].newtab) {
                    linkbtn += ' target="_blank"';
                }
                linkbtn += '>'+item.links[i].label+'</a></li>';
            }
            linkbtn += '</ul></span>';
            let linkspan = document.createElement("span");
            linkspan.className = "dropdown";
            linkspan.innerHTML = linkbtn;
            content.appendChild(linkspan);
        }
        li.appendChild(content);

        // Children container (rendered only when expanded)
        if (canHaveChildren && isExpanded && hasChildren) {
            const childrenContainer = document.createElement('ul');
            childrenContainer.className = 'tree-children expanded';
            childrenContainer.setAttribute('role', 'group');
            
            this.renderItems(item.children, childrenContainer, level + 1);
            li.appendChild(childrenContainer);
        }

        return li;
    }

    initializeCounts(items) {
        let cnt = 0;
        items.forEach(item => {
            if (item.children && item.children.length > 0) {
                const childrencnt = this.initializeCounts(item.children);
                item.count = childrencnt;
                cnt += childrencnt;
            } else if (item.count) {
                cnt += item.count;
            }
        });
        return cnt;
    }

    initializeSelection(items, overrides) {
        overrides = overrides || [];
        let containsSelection = false;
        items.forEach(item => {
            // Add to selection if item is marked as selected and can be selected
            if ((item.selected || overrides.includes(item.id)) && this.canSelectItem(item)) {
                if (this.options.selectionMode === 'single') {
                    // For single select, clear previous selections
                    this.selectedItems.clear();
                    this.selectedItems.add(item.id);
                } else {
                    // For multi-select, add to selection
                    this.selectedItems.add(item.id);
                }
                containsSelection = true;
            }
            if (item.expanded) {
                this.expandedItems.add(item.id);
            }
            
            // Recursively process children if they exist
            if (item.children && item.children.length > 0) {
                const subContainsSelection = this.initializeSelection(item.children, overrides);
                if (subContainsSelection) {
                    this.expandedItems.add(item.id);
                    if (overrides.length) {
                        // when using overrides, need to rerender to force expansion
                        this.reRenderItem(item.id);
                    }
                }
                containsSelection = containsSelection || subContainsSelection;
            } 
        });
        return containsSelection;
    }

    canSelectItem(item) {
        if (item.notselectable) { return false;}

        const hasChildren = (item.children && item.children.length > 0) || (item.childrenUrl && !this.loadedItems.has(item.id));
        
        switch (this.options.selectableItems) {
            case 'children':
                return !hasChildren;
            case 'parents':
                return hasChildren;
            case 'all':
            default:
                return true;
        }
    }

    bindEvents() {
        this.container.addEventListener('click', this.handleClick.bind(this));
        this.container.addEventListener('keydown', this.handleKeydown.bind(this));
        // prevents focus going to inputs and buttons
        this.container.addEventListener('mousedown', (e)=>e.preventDefault());
    }

    handleClick(event) {
        const treeItem = event.target.closest('.tree-item');
        if (!treeItem) return;

        if (event.target.closest('.dropdown')) {
            return;
        }

        const itemId = treeItem.getAttribute('data-id');
        const itemData = this.renderedItems.get(itemId)?.data;
        
        if (!itemData || itemData.disabled || itemData.locked) return;

        this.setFocus(treeItem);

        // Handle expander click
        if (event.target.classList.contains('tree-expander')) {
            this.toggleExpanded(itemId);
            return;
        }

        // Handle selection
        if (this.canSelectItem(itemData)) {
            this.toggleSelection(itemId);
        } else if ((itemData.children && itemData.children.length > 0) || (itemData.childrenUrl && !this.loadedItems.has(itemId))) {
            // If clicking on a non-selectable parent, toggle expansion
            this.toggleExpanded(itemId);
        }
    }

    handleKeydown(event) {
        const focusedElement = event.target.closest('.tree-item');
        if (!focusedElement) return;

        if (event.target.closest('.dropdown')) {
            return;
        }

        const itemId = focusedElement.getAttribute('data-id');
        const itemData = this.renderedItems.get(itemId)?.data;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.focusNext();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.focusPrevious();
                break;
            case 'ArrowRight':
                event.preventDefault();
                if ((itemData.children && itemData.children.length > 0) || (itemData.childrenUrl && !this.loadedItems.has(itemId))) {
                    if (!this.expandedItems.has(itemId)) {
                        this.toggleExpanded(itemId);
                    } else {
                        this.focusNext();
                    }
                }
                break;
            case 'ArrowLeft':
                event.preventDefault();
                if (this.expandedItems.has(itemId)) {
                    this.toggleExpanded(itemId);
                } else {
                    this.focusParent(focusedElement);
                }
                break;
            case ' ':
            case 'Enter':
                event.preventDefault();
                if (this.canSelectItem(itemData) && !itemData.disabled && !itemData.locked) {
                    this.toggleSelection(itemId);
                } else if ((itemData.children && itemData.children.length > 0) || (itemData.childrenUrl && !this.loadedItems.has(itemId))) {
                    this.toggleExpanded(itemId);
                }
                break;
            case 'Home':
                event.preventDefault();
                this.focusFirst();
                break;
            case 'End':
                event.preventDefault();
                this.focusLast();
                break;
        }
    }
    
    clearChildSelections(items) {
        items.forEach(item => {
            // Add to selection if item is marked as selected and can be selected
            if (this.selectedItems.has(item.id) && !item.locked) {
                this.selectedItems.delete(item.id);
            }
            // Recursively process children if they exist
            if (item.children && item.children.length > 0) {
                this.clearChildSelections(item.children);
            }
        });
    }

    toggleExpanded(itemId) {
        const item = this.renderedItems.get(itemId);
        if (!item) return;

        const hasChildren = item.data.children && item.data.children.length > 0;
        const hasChildrenUrl = item.data.childrenUrl && !this.loadedItems.has(itemId);
        const canHaveChildren = hasChildren || hasChildrenUrl;
        
        if (!canHaveChildren) return;

        const isExpanded = this.expandedItems.has(itemId);
        
        if (isExpanded) {
            // Collapse
            this.expandedItems.delete(itemId);
            if (hasChildren) {
                this.clearChildSelections(item.data.children);
            }
            this.options.onSelectionChange(this.getSelectedItems(), this.getSelectedNames());
            this.reRenderItem(itemId);
        } else {
            // Expand
            this.expandedItems.add(itemId);
            
            // If we need to load children, do so before expanding
            if (hasChildrenUrl) {
                this.loadChildren(itemId);
            } else {
                this.reRenderItem(itemId);
            }
        }
    }

    async loadChildren(itemId) {
        const item = this.renderedItems.get(itemId);
        if (!item || !item.data.childrenUrl || this.loadingItems.has(itemId) || this.loadedItems.has(itemId)) {
            return;
        }

        this.loadingItems.add(itemId);
        this.reRenderItem(itemId); // Show loading state

        try {
            const response = await fetch(item.data.childrenUrl);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const children = await response.json();
            
            // Update the item data with loaded children
            item.data.children = Array.isArray(children) ? children : [];
            this.loadedItems.add(itemId);

            this.initializeSelection(item.data.children);
            
        } catch (error) {
            // Handle error - remove from expanded items and notify
            this.expandedItems.delete(itemId);
            this.options.onLoadError(error, item.data);
        } finally {
            this.loadingItems.delete(itemId);
            this.reRenderItem(itemId);
        }
    }

    reRenderItem(itemId) {
        const item = this.renderedItems.get(itemId);
        if (!item) return;

        // Re-render the item to show updated state
        const parentElement = item.element.parentNode;
        const level = parseInt(item.element.getAttribute('aria-level'));
        const newElement = this.createTreeItem(item.data, level);
        
        parentElement.replaceChild(newElement, item.element);
        this.renderedItems.set(itemId, { element: newElement, data: item.data });
        
        // Update focus if this was the focused item
        if (this.focusedItem && this.focusedItem.getAttribute('data-id') === itemId) {
            this.setFocus(newElement);
        }
        this.updateSelectionUI();
    }

    toggleSelection(itemId) {
        const itemData = this.renderedItems.get(itemId)?.data;
        if (!itemData || !this.canSelectItem(itemData) || itemData.disabled || itemData.locked) return;

        if (this.options.selectionMode === 'single') {
            this.selectedItems.clear();
            this.selectedItems.add(itemId);
        } else {
            if (this.selectedItems.has(itemId)) {
                this.selectedItems.delete(itemId);
            } else {
                this.selectedItems.add(itemId);
            }
        }

        this.updateSelectionUI();
        this.options.onSelectionChange(this.getSelectedItems(), this.getSelectedNames());
    }

    updateSelectionUI() {
        const inputs = this.container.querySelectorAll('input[type="radio"], input[type="checkbox"]');
        inputs.forEach(input => {
            input.checked = this.selectedItems.has(input.value);
        });

        // Update visual selection state and ARIA attributes
        const items = this.container.querySelectorAll('.tree-item');
        items.forEach(item => {
            const itemId = item.getAttribute('data-id');
            const isSelected = this.selectedItems.has(itemId);
            const content = item.querySelector('.tree-item-content');
            const itemData = this.renderedItems.get(itemId)?.data;
            
            if (this.canSelectItem(itemData)) {
                if (this.options.selectionMode === 'multi') {
                    item.setAttribute('aria-checked', isSelected);
                } else {
                    item.setAttribute('aria-selected', isSelected);
                }
            }
            
            if (isSelected) {
                content.classList.add('selected');
            } else {
                content.classList.remove('selected');
            }
        });
    }

    setFocus(element) {
        if (this.focusedItem) {
            this.focusedItem.querySelector('.tree-item-content').tabIndex = -1;
        }
        
        this.focusedItem = element;
        const content = element.querySelector('.tree-item-content');
        content.tabIndex = 0;
        content.focus();
    }

    setInitialFocus() {
        const firstItem = this.container.querySelector('.tree-item');
        if (firstItem) {
            this.setFocus(firstItem);
        }
    }

    getVisibleItems() {
        return Array.from(this.container.querySelectorAll('.tree-item')).filter(item => {
            return item.offsetParent !== null;
        });
    }

    focusNext() {
        const visibleItems = this.getVisibleItems();
        const currentIndex = visibleItems.indexOf(this.focusedItem);
        const nextIndex = Math.min(currentIndex + 1, visibleItems.length - 1);
        this.setFocus(visibleItems[nextIndex]);
    }

    focusPrevious() {
        const visibleItems = this.getVisibleItems();
        const currentIndex = visibleItems.indexOf(this.focusedItem);
        const prevIndex = Math.max(currentIndex - 1, 0);
        this.setFocus(visibleItems[prevIndex]);
    }

    focusFirst() {
        const visibleItems = this.getVisibleItems();
        if (visibleItems.length > 0) {
            this.setFocus(visibleItems[0]);
        }
    }

    focusLast() {
        const visibleItems = this.getVisibleItems();
        if (visibleItems.length > 0) {
            this.setFocus(visibleItems[visibleItems.length - 1]);
        }
    }

    focusParent(element) {
        const level = parseInt(element.getAttribute('aria-level'));
        if (level <= 1) return;

        const visibleItems = this.getVisibleItems();
        const currentIndex = visibleItems.indexOf(element);
        
        for (let i = currentIndex - 1; i >= 0; i--) {
            const item = visibleItems[i];
            const itemLevel = parseInt(item.getAttribute('aria-level'));
            if (itemLevel < level) {
                this.setFocus(item);
                break;
            }
        }
    }

    getSelectedItems() {
        return Array.from(this.selectedItems);
    }
    getSelectedNames() {
        let names = [];
        this.selectedItems.forEach(item => {
            const itemData = this.renderedItems.get(item)?.data;
            names.push(itemData.label);
        });
        return names;
    }

    setSelectedItems(itemIds) {
        //this.selectedItems = new Set(itemIds);
        //this.updateSelectionUI();
        this.selectedItems.clear();
        this.initializeSelection(this.data, itemIds);
        this.updateSelectionUI();
    }

    unselectAll() {
        this.selectedItems.clear();
        this.updateSelectionUI();
    }
}
