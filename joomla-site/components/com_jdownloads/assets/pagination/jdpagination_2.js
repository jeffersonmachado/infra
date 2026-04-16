var jddiv = {};
jddiv.Pager = function() {
    this.paragraphsPerPage = 3;
    this.currentPage = 1;
	this.labelPage = '';
	this.labelOf = '';
    this.pagingControlsContainer = '#pageNavPosition';
    this.pagingContainerPath = '#results';

    this.numPages = function() {
        var numPages = 0;
        if (this.paragraphs != null && this.paragraphsPerPage != null) {
            numPages = Math.ceil(this.paragraphs.length / this.paragraphsPerPage);
        }
        
        return numPages;
    };

    this.showPage = function(page) {
        this.currentPage = page;
        var html = '';

        this.paragraphs.slice((page-1) * this.paragraphsPerPage,
            ((page-1)*this.paragraphsPerPage) + this.paragraphsPerPage).each(function() {
            html += '<div>' + jQuery(this).html() + '</div>';
        });

        jQuery(this.pagingContainerPath).html(html);

        renderControls(this.pagingControlsContainer, this.currentPage, this.numPages(), this.labelPage, this.labelOf);
    }

    var renderControls = function(container, currentPage, numPages, labelPage, labelOf) {
        var pagingControls = labelPage + ' ' + currentPage + ' ' + labelOf + ' ' + numPages + '<ul class="jd_pagination_list">';
        for (var i = 1; i <= numPages; i++) {
            if (i != currentPage) {
                pagingControls += '<li><a class="pagenav" href="#" onclick="pager.showPage(' + i + '); return false;">' + i + '</a></li>';
            } else {
                pagingControls += '<li class="disabled">' + i + '</li>';
            }
        }

        pagingControls += '</ul>';

        jQuery(container).html(pagingControls);
    }
}