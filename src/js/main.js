(function($){
	Array.prototype.unique = function() {
		var a = this.concat();
		for(var i=0; i<a.length; ++i) {
			for(var j=i+1; j<a.length; ++j) {
				if(a[i] === a[j])
					a.splice(j--, 1);
			}
		}
		return a;
	};
	var avonCat = null,
		domUrl = window.domUrl || document.location.origin + "/docs/",
		AvonCatalog = function(element, data){
			var self = this,
				$book = 
					$("<div class=\"book initializing\">" +
						"<div class=\"book-wrapper\">" +
							"<div class=\"turn\"></div>" +
						"</div>" +
						"<div class=\"book-slider turnjs-slider\">" +
							"<div class=\"jsslider\"></div>" +
						"</div>" +
						"<div class=\"products\"></div>" +
					"</div>");
			self.element = element;
			self.turn = $('.turn', $book);
			self.slider = $('.jsslider', $book);
			self.productsWrapper = $('.products', $book);
			self.index = -1;
			self.pageWidth = self.pageHeight = 0;
			self.pages = [];
			self.page = 0;
			self.book = {};
			self.req = null;
			self.jsTurn = null;
			self._thumbPreview = null;
			self.select = $(data.select);
			self.data = data;
			$(element).append([self.select, $book]);
			self.bookwrapper = $book;
			$(element).data({
				'data': self
			});
			self.select.data({
				'data': self
			});
			self.init();
		};
	$.extend(AvonCatalog.prototype, {
		init: function(){
			var self = this;
			self.select.on('change', self.change.bind(self));
			$(window).on('resize', self.windowresize.bind(self));
			$(self.element).on('click', '.zoom-img', self.fancyBoxPage);
		},
		loadPage: function(){
			var $img = $(this),
				page = $img.attr('data-page'),
				data = avonCat.jsTurn.data(),
				parent = data.pageObjs[page];
			parent.removeClass('preloader');
			$('.preloader, .imghide', parent).removeClass("preloader imghide");
			$img.css({opacity: 1});
		},
		loadThumb: function() {
			var self = this,
				img = $('img', self._thumbPreview);
			if(self._thumbPreview) {
				setTimeout(function() {
					img.show();
					var a = $(self._thumbPreview).parent().outerWidth(),
						b = -($(self._thumbPreview).outerWidth() - a) / 2,
						mw = parseInt($(self._thumbPreview).css('min-width')),
						iw = img.width(),
						mmw = Math.max(mw, iw);
					$(self._thumbPreview).css({
						'min-width': mmw+"px"
					});
					$(self._thumbPreview).css({
						left: b
					});
				}, 200);
			}
        },
		fancyBoxPage: function(e){
			e.preventDefault();
			var $this = $(this),
				$parent = $this.parent(),
				$img = $('img', $parent),
				src = $img.attr('src'),
				data = {
					src  : src,
					opts : {
						caption : $img.attr('alt'),
						thumb   : $img.attr('data-thumb')
					}
				};
			if(src){
				$.fancybox.open([data]);
			}
			return !1;
		},
		sliderThumbnail: function(a){
			var self = this;
			console.log(a);
			if(self._thumbPreview){
				var b = self.pages[a - 1].thumb,
					c = $(self._thumbPreview),
					d = c.children(":first"),
					e = (d.outerWidth(), -(c.outerWidth() - c.parent().outerWidth()) / 2),
					selThmb = $('img', self._thumbPreview);
				//1 == a || self.slider.slider("option", "max");
				c.addClass("no-transition").css({
					left: e
				});
				d.css({
					position: "relative"
				}).attr({
					"data-page": a
				});
				if(!selThmb.length){
					selThmb = $('<img />').addClass('img-responsive thumbnail-catalog');
					d.empty().append(selThmb);
				}
				selThmb[0].onload = selThmb[0].onerror = self.loadThumb.bind(self);
				selThmb[0].src = b;
				//.html('<img class="img-responsive thumbnail-catalog" src="' + b + '" onload="onLoadThumbFn()" />');
				if("" === d.css("background-image") || "none" == d.css("background-image")){
					d.css({});
					setTimeout(function() {
						c.removeClass("no-transition").css({
							left: e
						});
					}, 0);
				}
				setTimeout(function() {
					c.removeClass("no-transition").css({
						left: e
					});
				}, 200);
			}
		},
		missing: function(event, pages){
			var self = this,
				turn = self.jsTurn;
			
			pages.forEach(function(value, index, array){
				if (turn.turn("pages"), !turn.turn("hasPage", value)) {
					var src = self.pages[value-1].image,
						thumb = self.pages[value-1].thumb,
						caption = "Страница №" + value,
						preloader = $("<div />", {
								'class': "divshadow preloader"
						}),
						zoom = $("<span />", {
							'class': "zoom-img icon-zoom-in",
						}),
						img = $(new Image()).attr({
							'class': "img-responsive imghide",
							"data-page": value,
							'alt': caption,
							'data-thumb': thumb
						}),
						ownsize = $("<div />", {
							'class': "own-size preloader"
						});
					var tmpThumb = new Image();
					tmpThumb.src = thumb;
					ownsize.append([preloader, img, zoom]);
					turn.turn("addPage", ownsize, value);
					setTimeout(function(){
						var i = img[0];
						i.onload = self.loadPage.bind(i);
						i.onerror = self.loadPage.bind(i);
						i.src = src;
					}, 50);
				}
			});
			
		},
		change: function(e){
			var self = this,
				$this = self.select,
				val = parseInt($this.val());
			if(self.index != val){
				if(self.req){
					self.req.abort();
					self.req = null;
				}
				self.index = val;
				self.book = self.data.catalogs[self.index];
				self.pages = self.data.catalogs[self.index].pages;
				self.pageWidth = self.pages[0].width;
				self.pageHeight = self.pages[0].height;
				self.bookwrapper.addClass('initializing');
				setTimeout(function(){
					
					if(self.jsTurn){
						self.jsTurn.turn('destroy');
						self.slider.slider("destroy");
						$('.thumbnail', self.slider).remove();
					}
					self._thumbPreview = null;
					$("*", self.turn).unbind();
					self.turn.empty();
					self.page = 1;
					self.jsTurn = $("<div class=\"sj-book\"></div>");
					self.turn.append(self.jsTurn);
					
					setTimeout(function(){
						self.initTurn();
						
						$([self.element, self.jsTurn[0], $this[0]]).data({
							'data': self
						});
					}, 700);
				}, 300);
			}
		},
		
		initTurn: function(){
			var self = this,
				catalogWidth = $('.turn', self.element).outerWidth() - 60,
				width = catalogWidth,
				height = catalogWidth / (2 * self.pageWidth) * self.pageHeight,
				display = self.pages[0].width < self.pages[0].height ? "double" : "single";
			
			self.slider.slider({
				min: 1,
				max: self.pages.length,
				start: function(a, b) {
					if(self._thumbPreview) {
						self.sliderThumbnail(b.value);
					} else {
						self._thumbPreview = $("<div />", {
							class: "thumbnail"
						}).html("<div></div>");
						self.sliderThumbnail(b.value);
						self._thumbPreview.appendTo($(b.handle));
					}
				},
				slide: function(a, b) {
					//console.log(b);
					self.sliderThumbnail(b.value);
				},
				stop: function() {
					if(self._thumbPreview) {
						self._thumbPreview.removeClass("show");
						self.jsTurn.turn("page", Math.max(1, $(this).slider("value")));
					}
				}
			});
			self.jsTurn.css({
				
			}).turn({
				elevation: 50,
				acceleration: false,
				autoCenter: true,
				gradients: true,
				duration: 800,
				pages: self.pages.length,
				width: width,
				height: height,
				page: self.page,
				display: "double",
				when: {
					turning: function(event, page, pageObject) {
						var $this = $(this),
							index = $this.turn("page"),
							length = $this.turn("pages");
						if (index > 3 && length - 3 > index) {
							if (1 == page) {
								return $this.turn("page", 2).turn("stop").turn("page", page);
							}
							if (page == length){
								return $this.turn("page", length - 1).turn("stop").turn("page", page);
							}
						} else if (page > 3 && length - 3 > page) {
							if (1 == index) {
								return $this.turn("page", 2).turn("stop").turn("page", page);
							}
							if (index == length) {
								return $this.turn("page", length - 1).turn("stop").turn("page", page);
							}
						}

						$this.turn('page'); $this.turn("pages");
					},
					turned: function(event, page, pageObject){
						var $this = $(this),
							doublePage = {};
						if(self.req){
							self.req.abort();
						}
						self.page = parseInt(page || $this.turn("page"), 10);
						var c = self.page,
							g = self.page % 2,
							j = self.pages.length;
						self.slider.slider("value", c);
						doublePage = {
							double: !(1 == c || c == self.pages.length),
							start: 0 == g ? c == j ? j : c : c == j ? j : 1 == c ? c : c - 1
						};
						self.getProducts(doublePage);
						
					},
					missing: self.missing.bind(self),
					start: function(a, b) {},
					
				}
			});
			self.bookwrapper.removeClass('initializing');
		},
		getProducts: function(object){
			var self = this,
				productsWrapper = $("<div></div>", {
					'class' : 'products-wrapper'
				});
			self.productsWrapper.empty();
			var doubl = object.double,
				start = object.start,
				pages = self.pages[start-1].products.slice(),
				products = [],
				outproducts = [],
				doublPages = doubl ? self.pages[start].products.slice() : [],
				doublProducts = [],
				doublOutproducts = [],
				getAjax = function(param, page){
					if(param.length && parseInt(page)){
						var data = {
							modal: param.join(","),
							page: parseInt(page),
							compaing: self.data.compaing,
							index: self.index + 1,
						};
						self.req = $.ajax({
							url: domUrl + 'function.php',
							data: data,
							method: 'POST',
							type: 'json',
							success: callBackAjax.bind(self),
							error: function(){
								callBackAjax(null);
							}
						});
					}
				},
				callBackAjax = function(data){
					var selfcat = this,
						selfdata = selfcat.data;
					if(data){
						if($.isArray(data)){
							selfcat.productsWrapper.append(productsWrapper);
							var products = [];
							data.forEach(function(item, index, array){
								var element = $("<div></div>", {
										'class': 'product clearfix'
									}),
									elements = [
										$("<div></div>", {
											'class': 'product-title'
										}).append($("<strong></strong>", {
											'text' : item.Product.Name
										})),
										$("<div></div>", {
											'class': 'product-image',
											'data-image': selfdata.cdnRoot + "assets/ru-ru/images/product/prod_" + item.Product.ProfileNumber.toLowerCase() + "_1_613x613.jpg"
										}).append([
											$("<img />", {
												'src': selfdata.cdnRoot + "assets/ru-ru/images/product/prod_" + item.Product.ProfileNumber.toLowerCase() + "_1_613x613.jpg",
												'alt': ''
											}), 
											$("<span></span>", {
												'class': (item.Product.SingleVariantFsc ? "product-code" : ""),
												'text': (item.Product.SingleVariantFsc ? item.Product.SingleVariantFsc : "")
											}),
											$("<span></span>", {
												'class': (item.Product.SalePriceFormatted ? "product-price" : ""),
												'text': (item.Product.SalePriceFormatted ? item.Product.SalePriceFormatted : "")
											}),
										]),
										$("<div></div>", {
											'class': 'product-description'
										}).html(item.Description),
										$("<div></div>", {
											'class': 'clearfix'
										})
									];
								if(item.HasShadeVariants){
									var shades = $("<div></div>", {
											'class': 'shades-wrapper'
										});
									elements.push($("<div></div>", {
										'class' : 'shades'
									}).append(shades));
									item.AllVariants.forEach(function(varitem, varindex, vararray){
										if(varitem.IsAvailable){
											var column = $("<div></div>", {
												'class' : 'shade'
											}).append([
												$("<div></div>", {
													'class': 'shade-code',
													'text': varitem.DisplayLineNumber
												}),
												$("<img />", {
													'class': 'shade-iamge',
													'src': varitem.Image
												}),
												$("<div></div>", {
													'class': 'shade-name',
													'text': varitem.Name
												})
											]);
											
											shades.append(column);
										}
									});
								}
								
								productsWrapper.append(element.append(elements));
								$("*", productsWrapper).each(function(){
									$(this).removeAttr('style');
									if(this.tagName == 'A'){
										$(this).attr({
											'target': "_blank"
										});
									}
								});
								if(item.HasNonShadeVariants){
									//product.groups.push(item.Product.VariantGroups[0]);
								}
							});
							//console.log(products);
						}
					}
					if(doubl){
						doubl = false;
						if(doublOutproducts.length){
							getAjax(doublOutproducts, start+1);
						}
					}
				};
				
			pages.forEach(function(item, index, array){
				products.push(item.ProductId);
			});
			doublPages.forEach(function(item, index, array){
				doublProducts.push(item.ProductId);
			});
			outproducts = self.filterArray(products);
			doublOutproducts = self.filterArray(doublProducts);
			var unout = outproducts.slice(),
				undbl = doublOutproducts.slice(),
				full = unout.concat(undbl).unique();
			if(full.length < 3){
				outproducts = full;
				doubl = false;
			}
			if(outproducts.length){
				getAjax(outproducts, start);
			}else{
				doubl = false;
				getAjax(doublOutproducts.length, start+1);
			}
		},
		filterArray: function(array){
			var tmpArray = array.slice(),
				returnArray = tmpArray.filter(function(item, pos) {
					return tmpArray.indexOf(item) == pos;
				});
			return returnArray;
		},
		windowresize: function(e){
			var self = this,
				$element = $(self.element);
			if(-1 != self.page && self.jsTurn){
				self.jsTurn.turn('destroy');
				self.initTurn();
			}
		}
	});
	
	
	var init = function(data) {
			avonCat = new AvonCatalog($(".avoncatalog")[0], data);
			//console.log(data);
		};
	
	$.ajax({
		url: domUrl + 'function.php?catalog',
		method: 'GET',
		type: 'json',
		success: init
	});
	
}(jQuery));