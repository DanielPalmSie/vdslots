var cashierExpander = {
    boxes: [],
    
    init: function (){
        jQuery.each($(".cashierBox"), function () {
            var index = cashierExpander.boxes.push(new CashierBox($(this), $(this).find(".slideTrigger"), $(this).find(".cashierBoxInsert"), $(this).find(".arrow"), false)) - 1;


            $(this).find(".slideTrigger").click(function(){
	        cashierExpander.expandCollapse(index);
            });            
        });

        _.each(cashierExpander.boxes, function(el){
            el.close();
        })
    },
    
    expandCollapse: function(index){
        for(var i in cashierExpander.boxes) {

            var curEl = cashierExpander.boxes[i];
            
            if(i == index){
	        if(!curEl.expanded){
	            curEl.expand();
	        }else{
	            curEl.collapse();
	        }
            }else{
	        if(curEl.expanded){
	            curEl.collapse();
	        }						
            }
        }
        return false;
    }
};

function CashierBox(container, trigger, slideElement, icon, expanded){
    this.container = container;
    this.trigger = trigger;
    this.slideElement = slideElement;
    this.icon = icon;
    this.expanded = expanded;
    
    CashierBox.prototype.expand = function(){
        this.slideElement.stop(true, true).slideDown();
        this.expanded = true;
        this.icon
	    .removeClass("closed")
	    .addClass("open");
        this.container.css("border-color", "#333");						
    }
    
    CashierBox.prototype.collapse = function(){
        this.slideElement.stop(true, true).slideUp();
        this.expanded = false;
        this.icon
	    .removeClass("open")
	    .addClass("closed");
        this.container.css("border-color", "#B5B5B5");
    }
    
    CashierBox.prototype.close = function() {
        this.slideElement.hide();
        this.expanded = false;
        this.icon
	    .removeClass("open")
	    .addClass("closed");					
    }
    
    CashierBox.prototype.open = function() {
        this.slideElement.show();
        this.expanded = true;
        this.icon
	    .removeClass("closed")
	    .addClass("open");
    }
};

$(document).ready(cashierExpander.init);
