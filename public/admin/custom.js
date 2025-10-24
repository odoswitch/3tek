// Personnalisation des textes EasyAdmin en français
document.addEventListener('DOMContentLoaded', function() {
    // Remplacer "Add a new item" par "Ajouter une image"
    const addButtons = document.querySelectorAll('.field-collection-add-button');
    addButtons.forEach(button => {
        if (button.textContent.includes('Add a new item')) {
            button.innerHTML = '<i class="fa fa-plus"></i> Ajouter une image';
        }
    });
    
    // Remplacer "Empty" par "Aucune image ajoutée"
    const emptyMessages = document.querySelectorAll('.form-widget-compound .form-group:empty');
    emptyMessages.forEach(msg => {
        if (msg.textContent.trim() === 'Empty' || msg.textContent.trim() === '') {
            msg.innerHTML = '<span class="text-muted"><i class="fa fa-image"></i> Aucune image ajoutée</span>';
        }
    });
    
    // Observer pour les éléments ajoutés dynamiquement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    const buttons = node.querySelectorAll('.field-collection-add-button');
                    buttons.forEach(button => {
                        if (button.textContent.includes('Add a new item')) {
                            button.innerHTML = '<i class="fa fa-plus"></i> Ajouter une image';
                        }
                    });
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
