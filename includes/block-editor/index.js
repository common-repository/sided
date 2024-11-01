// block.js
(function(blocks, element, blockEditor) {
	const htmlToElem = ( html ) => wp.element.RawHTML( { children: html } );
	var el = element.createElement,
		useBlockProps = blockEditor.useBlockProps,
		currentRequest = null;
	blocks.registerBlockType('sided/sided-debate-selector', {
		apiVersion: 1,
		title: 'Select Poll',
		icon: el("img", {
			src: "https://app.sided.co/favicon.ico",
			alt: "sided",
			height: "20px",
			width: "20px",
		}, ),
		description: "Insert a debate from existing debates.",
		category: "widgets",
		attributes: {
			selectedDebate: {
				type: "integer",
				default: null
			},
			searchKeyword: {
				type: "string",
				default: null
			},
			embedded: {
				type: "string",
				default: null
			},
			selectedDebateData:{
				type: "object",
				default: null
			}
		},

		edit: function(props) {
			window.props = props;
			console.log(props);
			function searchDebate(event) {
				var $ = jQuery;
				var thisElement = $(event.target);		
				props.setAttributes({
					searchKeyword: event.target.value
				});
				if (event.target.value.length > 0 && event.target.value.length < 3) {
					return false;
				}
				if (currentRequest != null) currentRequest.abort();
				currentRequest = $.ajax({
					url: ajaxurl,
					type: 'get',
					data: {
						action: 'wpa_fetch_debates',
						searchText: event.target.value,
						results_per_page: event.target.value.length === 0 ? 10 : 999
					},
					success: function(data) {
						thisElement.next('.searchResult').html('');
						var dataArray = $.parseJSON(data);
						if (dataArray.count > 0) {
							$.each(dataArray.rows, function(key, value) {
								thisElement.next('.searchResult').append('<li><a class="debate-item" data-did="' + value['id'] + '">' + value['thesis'] + '</a></li>');
							});
						} else {
							thisElement.next('.searchResult').append('<li>No debate found!</li>');
						}

						var items = document.getElementsByClassName('searchResult');
						for (var i = 0; i < items.length; i++) {
						  items[i].addEventListener("click", insertShortcode);
						}
					},
					error: function(data) {
						thisElement.next('.searchResult').html('');
						//thisElement.next('.searchResult').append('<li>Error occured!</li>');
					}
				})
				event.preventDefault();
			}

			function insertShortcode(event){
				jQuery(this).parents('.sided-wp-plugin-wrapper').addClass('loadingPollPreview');
				selectedDebate(jQuery(event.target).data('did'));
				if (event.target && event.target.matches("a.debate-item")) {
					var $ = jQuery;
					var thisElement = $(event.target);
					var dId = thisElement.data('did');
					var dThesis = thisElement.text();
					props.setAttributes({
						selectedDebate: Number(dId),
						searchKeyword: dThesis,
						embedded: 'debate-embedded'
					});
					thisElement.parents('.searchResult').html('');
				}
			}

			function selectedDebate(debateId){
				props.setAttributes({
					selectedDebateData: ''
				});
				var $ = jQuery;
				$.ajax({
					url: ajaxurl,
					type: 'get',
					data: {
						action: 'wpa_fetch_current_debate',
						debateId: debateId
					},
					success: function(data) {
						var dataArray = $.parseJSON(data);
						props.setAttributes({
							selectedDebateData: dataArray
						});
						$('.loadingPollPreview').removeClass('loadingPollPreview');
						console.log(props.attributes.selectedDebateData);
					},
					error: function(data) {
						console.log(data);
					}
				});
			}

			function editBlockBtnClick(event) {
				props.setAttributes({
					embedded: props.attributes.embedded == '' ? 'debate-embedded' : '',
					//selectedDebateData: ''
				});
			}
			function clearSearch(event){
				props.setAttributes({
					searchKeyword: '',
				});
				currentRequest.abort();
			}

			children = [];

			children.push(
				el(
					'label', {
						className: ""
					},
					"Search Polls"
				),
				el(
					'div', {
						className: "searchKeyword-wrap",
					},
					props.attributes.searchKeyword && props.attributes.searchKeyword.length > 0 ? el(
						'span', {
							onClick: clearSearch
							},
							'+'
					) : '',
					el(
						'input', {
							className: "searchKeyword",
							placeholder: "Type here",
							value: props.attributes.searchKeyword,
							onChange: searchDebate,
							onFocus: searchDebate,
							tabIndex:"-1"
						}
					),
					el(
						'ul', {
							className: "searchResult"
						}
					),
				),
				props.attributes.selectedDebate ? el(
					'button', {
						className: "edit-block-btn ",
						onClick: editBlockBtnClick
					},
					'Cancel'
				) : ''
			);


			debatePreview = [];
			if(props.attributes.selectedDebateData){
				var sdd = props.attributes.selectedDebateData;
				var endsIn = moment(sdd.endedAt).diff( moment(), "months" ) > 0 ?
							 moment(sdd.endedAt).diff( moment(), "months" ) +" month(s)"
							 : (
							 	moment(sdd.endedAt).diff( moment(), "days" ) > 0 ?
							 	moment(sdd.endedAt).diff( moment(), "days" ) +" day(s)"
							 	: (
							 		moment(sdd.endedAt).diff( moment(), "hours" ) > 0 ?
							 		moment(sdd.endedAt).diff( moment(), "hours" ) +" hour(s)"
							 		: (
							 			moment(sdd.endedAt).diff( moment(), "minutes" ) > 0 ?
							 			moment(sdd.endedAt).diff( moment(), "minutes" ) +" min(s)"
							 			: 'Few seconds'
							 			)
							 		)
							 	) 
				debateSides = [];
				if(sdd.sides){
					for(let i=0 ; i<sdd.sides.length ; i++){
						if(sdd.isActive){
							debateSides.push(
								el(
							        "div",
							        { className: "" },
							        el(
							          "label",
							          null,
							          props.attributes.selectedDebateData.sides[i].text
							        )
							      )
							)
						} else {
							debateSides.push(
								el(
							        "div",
							        { className: "side" },
									el(
										"div",
										{ className: "message1 position-relative" },
										!isNaN((sdd.sides[i].votes * 100 / sdd.votes).toFixed(2)) ? el("div", { className: "percentageBar", style: { width: sdd.sides[i].votes * 100 / sdd.votes + '%', background: sdd.sides[i].sideColor + '26' } }) : null,
										el(
											"div",
											{ className: "topDebateSides", style: { borderColor: sdd.sides[i].sideColor, background: sdd.sides[i].sideColor + '10' } },
											sdd.sides[i].text
										),
										!isNaN((sdd.sides[i].votes * 100 / sdd.votes).toFixed(2)) ? el(
											"span",
											{ className: "votesPercentage" },
											(sdd.sides[i].votes * 100 / sdd.votes).toFixed(2),
											"%",
										) : el(
											"span",
											{ className: "votesPercentage" },
											
										)
									 )
								)
							)
						}
					}
				}
				debatePreview.push(
					el(
				      "div",
				      { className: "debatePreviewSection" },
				      el(
				        "div",
				        { className: "debatePreviewInner" },
				        el(
				          "div",
				          { className: "debatePreviewHeader" },
				          el(
				            "div",
				            { className: "author" },
				            el(
				              "a",
				              { target: "_blank", rel: "noreferrer", className: "small-dp user-color-green",  tabindex: "0" },
				              el("img", { "data-src": "", src: sdd.user.avatarObject ? sdd.user.avatarObject.small.location : 'https://cdn.sided.co/prod/sided/users/default-avatar.png', alt: sdd.user.username, className: "sidedLazyLoad img-circle avatar" }),
				              sdd.user.roles && sdd.user.roles.length && sdd.user.roles.find(role => role.name === 'VERIFIED') ? el(
				                "svg",
				                { "aria-hidden": "true", focusable: "false", "data-prefix": "fas", "data-icon": "circle-check", className: "svg-inline--fa fa-circle-check procheckmark", role: "img", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 512 512" },
				                el("path", { fill: "currentColor", d: "M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" })
				              ) : null
				            ),
				            el(
				              "div",
				              { className: "copy-wrap" },
				              el(
				                "a",
				                { target: "_blank", rel: "noreferrer", className: "authorname", tabindex: "0" },
				                el(
				                  "h6",
				                  null,
				                  sdd.user.username
				                )
				              ),
				              el(
				                "a",
				                { rel: "noreferrer", className: "authorhandle" },
				                el(
				                  "span",
				                  { className: "handler" },
				                  "Posted "+moment(sdd.startedAt).format('DD MMM, YYYY')
				                )
				              )
				            )
				          )
				        ),
				        el(
				          "div",
				          { className: "debatePreviewTitle mt-3" },
				          el(
				            "h5",
				            null,
				            sdd.thesis
				          )
				        ),
				        el(
				          "span",
				          { className: "lightColorSpan mt-2 d-block" },
				          sdd.isActive ? (sdd.endedAt ? "Poll ends in "+endsIn+"  • Vote below" : 'Vote below') : 'Poll ended '+moment(sdd.endedAt).format('MMM DD')
				        ),
				        el(
				          "div",
				          { className: "debatePreviewSides mt-2" },
				          debateSides
				        ),
				        el(
				          "div",
				          { className: "inline-content middle mt-3" },
				          el(
				            "span",
				            { className: "lightColorSpan d-block" },
				            sdd.votes+" Votes \u2022 "+sdd.comments+" Comment "
				          ),
				          el(
				            "a",
				            { className: "customAnchor" },
				            "Share"
				          )
				        ),
				        el(
				          "div",
				          { className: "debatePreviewFooter mt-3" },
				          el(
				            "a",
				            { className: "customAnchor" },
				            "Embed Poll"
				          ),
				          el("img", { alt: "Logo", src: "https://cdn.sided.co/prod/sided/wl/sided/images/c7ac0980-0852-11eb-b673-1dad531e23be.png", className: "logo" })
				        )
				      )
				    )
				);
			} else {
				debatePreview.push('[sided-debate-embed debate-id="' + props.attributes.selectedDebate + '"]');
			}

			var sidedPrelaoder = [];
			sidedPrelaoder.push(
				el(
	              "div",
	              { "class": "preloader-dot-loading" },
	              el(
	                            "div",
	                            { "class": "cssload-loading" },
	                            el("i", null),
	                            el("i", null),
	                            el("i", null),
	                            el("i", null)
	            	)
				)
			);

			var embeddedClass = props.attributes.embedded;

			return el(
					'div', {
						className: "sided-wp-plugin-wrapper"
					},
					el(
						'div', {
							className: "components-placeholder " + embeddedClass
						},
						el(
							'div', {
								className: "edit-block-wrap ",
								/*onBlur: searchDebate,*/
							},
							children
						),
						el(
							'div', {
								className: "confirm-block"
							},
							debatePreview,
							sidedPrelaoder,
							el(
								'button', {
									className: "edit-block-btn",
								onClick: editBlockBtnClick
								},
								'Edit Poll'
							)
						)
					),
			);

			/*return htmlToElem("<div style='border:1px solid red' className='sided-widget' debateid='"+props.attributes.selectedDebate+"'></div><button className='edit-block-btn' onclick='editBlockBtnClick();'>Edit Poll</button>");*/
		},

		save: function(props) {
			var dId = props.attributes.selectedDebate;
			var sDebData = props.attributes.selectedDebateData;
			if (dId === null) {
				return null;
			}
			return '[sided-debate-embed debate-id="' + dId + '"]';
		},
	});



	var el = element.createElement,
		useBlockProps = blockEditor.useBlockProps,
		currentRequest = null;

	blocks.registerBlockType('sided/sided-debate-creator', {
		apiVersion: 1,
		title: 'Create Poll',
		icon: el("img", {
			src: "https://app.sided.co/favicon.ico",
			alt: "sided",
			height: "20px",
			width: "20px",
		}, ),
		description: "Create new debate and embed into page.",
		category: "widgets",
		attributes: {
			selectedDebate: {
				type: "integer",
				default: null
			},
			embedded: {
				type: "string",
				default: false
			},
			debateCreated: {
				type: "string",
				default: false
			},
			selectedDebateData:{
				type: "object",
				default: null
			}
			

		},

		edit: function(props) {
			window.props = props;

			window.addEventListener('message', function(e){
				if(e.data.did) {
					window.selectedDebateId = e.data.did;
					props.setAttributes({
						debateCreated: true
					});
				}

			});

			function setSelectedDebate(){
				props.setAttributes({
					selectedDebate: Number(selectedDebateId),
					embedded: true
				});
				jQuery('.is-selected .sided-wp-plugin-wrapper').addClass('loadingPollPreview');
				selectedDebate(selectedDebateId);
			}

			function selectedDebate(debateId){
				var $ = jQuery;
				$.ajax({
					url: ajaxurl,
					type: 'get',
					data: {
						action: 'wpa_fetch_current_debate',
						debateId: debateId
					},
					success: function(data) {
						var dataArray = $.parseJSON(data);
						props.setAttributes({
							selectedDebateData: dataArray
						});
						$('.loadingPollPreview').removeClass('loadingPollPreview');
					},
					error: function(data) {
						alert('Debate embedded successfully. Preview cannot be loaded due to some error.');
						$('.loadingPollPreview').removeClass('loadingPollPreview');
					}
				});
			}

			debatePreview = [];
			if(props.attributes.selectedDebateData){
				var sdd = props.attributes.selectedDebateData;
				var endsIn = moment(sdd.endedAt).diff( moment(), "months" ) > 0 ?
							 moment(sdd.endedAt).diff( moment(), "months" ) +" month(s)"
							 : (
							 	moment(sdd.endedAt).diff( moment(), "days" ) > 0 ?
							 	moment(sdd.endedAt).diff( moment(), "days" ) +" day(s)"
							 	: (
							 		moment(sdd.endedAt).diff( moment(), "hours" ) > 0 ?
							 		moment(sdd.endedAt).diff( moment(), "hours" ) +" hour(s)"
							 		: (
							 			moment(sdd.endedAt).diff( moment(), "minutes" ) > 0 ?
							 			moment(sdd.endedAt).diff( moment(), "minutes" ) +" min(s)"
							 			: 'Few seconds'
							 			)
							 		)
							 	) 
				debateSides = [];
				if(sdd.sides){
					for(let i=0 ; i<sdd.sides.length ; i++){
						if(sdd.isActive){
							debateSides.push(
								el(
							        "div",
							        { className: "" },
							        el(
							          "label",
							          null,
							          props.attributes.selectedDebateData.sides[i].text
							        )
							      )
							)
						} else {
							debateSides.push(
								el(
							        "div",
							        { className: "side" },
									el(
										"div",
										{ className: "message1 position-relative" },
										!isNaN((sdd.sides[i].votes * 100 / sdd.votes).toFixed(2)) ? el("div", { className: "percentageBar", style: { width: sdd.sides[i].votes * 100 / sdd.votes + '%', background: sdd.sides[i].sideColor + '26' } }) : null,
										el(
											"div",
											{ className: "topDebateSides", style: { borderColor: sdd.sides[i].sideColor, background: sdd.sides[i].sideColor + '10' } },
											sdd.sides[i].text
										),
										!isNaN((sdd.sides[i].votes * 100 / sdd.votes).toFixed(2)) ? el(
											"span",
											{ className: "votesPercentage" },
											(sdd.sides[i].votes * 100 / sdd.votes).toFixed(2),
											"%",
										) : el(
											"span",
											{ className: "votesPercentage" },
											
										)
									 )
								)
							)
						}
					}
				}
				debatePreview.push(
					el(
				      "div",
				      { className: "debatePreviewSection" },
				      el(
				        "div",
				        { className: "debatePreviewInner" },
				        el(
				          "div",
				          { className: "debatePreviewHeader" },
				          el(
				            "div",
				            { className: "author" },
				            el(
				              "a",
				              { target: "_blank", rel: "noreferrer", className: "small-dp user-color-green",  tabindex: "0" },
				              el("img", { "data-src": "", src: sdd.user.avatarObject ? sdd.user.avatarObject.small.location : 'https://cdn.sided.co/prod/sided/users/default-avatar.png', alt: sdd.user.username, className: "sidedLazyLoad img-circle avatar" }),
				              sdd.user.roles && sdd.user.roles.length && sdd.user.roles.find(role => role.name === 'VERIFIED') ? el(
				                "svg",
				                { "aria-hidden": "true", focusable: "false", "data-prefix": "fas", "data-icon": "circle-check", className: "svg-inline--fa fa-circle-check procheckmark", role: "img", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 512 512" },
				                el("path", { fill: "currentColor", d: "M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" })
				              ) : null
				            ),
				            el(
				              "div",
				              { className: "copy-wrap" },
				              el(
				                "a",
				                { target: "_blank", rel: "noreferrer", className: "authorname", tabindex: "0" },
				                el(
				                  "h6",
				                  null,
				                  sdd.user.username
				                )
				              ),
				              el(
				                "a",
				                { rel: "noreferrer", className: "authorhandle" },
				                el(
				                  "span",
				                  { className: "handler" },
				                  "Posted "+moment(sdd.startedAt).format('DD MMM, YYYY')
				                )
				              )
				            )
				          )
				        ),
				        el(
				          "div",
				          { className: "debatePreviewTitle mt-3" },
				          el(
				            "h5",
				            null,
				            sdd.thesis
				          )
				        ),
				        el(
				          "span",
				          { className: "lightColorSpan mt-2 d-block" },
				          sdd.isActive ? (sdd.endedAt ? "Poll ends in "+endsIn+"  • Vote below" : 'Vote below') : ''
				        ),
				        el(
				          "div",
				          { className: "debatePreviewSides mt-2" },
				          debateSides
				        ),
				        el(
				          "div",
				          { className: "inline-content middle mt-3" },
				          el(
				            "span",
				            { className: "lightColorSpan d-block" },
				            sdd.votes+" Votes \u2022 "+sdd.comments+" Comment "
				          ),
				          el(
				            "a",
				            { className: "customAnchor" },
				            "Share"
				          )
				        ),
				        el(
				          "div",
				          { className: "debatePreviewFooter mt-3" },
				          el(
				            "a",
				            { className: "customAnchor" },
				            "Embed Poll"
				          ),
				          el("img", { alt: "Logo", src: "https://cdn.sided.co/prod/sided/wl/sided/images/c7ac0980-0852-11eb-b673-1dad531e23be.png", className: "logo" })
				        )
				      )
				    )
				);
			} else {
				debatePreview.push('[sided-debate-embed debate-id="' + props.attributes.selectedDebate + '"]');
			}

			var sidedPrelaoder = [];
			sidedPrelaoder.push(
				el(
	              "div",
	              { "class": "preloader-dot-loading" },
	              el(
	                            "div",
	                            { "class": "cssload-loading" },
	                            el("i", null),
	                            el("i", null),
	                            el("i", null),
	                            el("i", null)
	            	)
				)
			);

			children = [];

			children.push(
				el(
					'div', {
						className: "searchKeyword-wrap",
					},
					props.attributes.selectedDebate || props.attributes.debateCreated ? '' : el(
						'iframe', {
							src: 'admin.php?page=create-debate-from-block&&sided_hide_force=true'
						}
					),
					props.attributes.selectedDebate ? el(
						'div', {
							className: "textCenter"
						},
						debatePreview,
						sidedPrelaoder,
					) : '',
					props.attributes.selectedDebate || props.attributes.debateCreated === false ? '' : el(
							'p', {
							},
							'Success! Your poll has been created.'
							),
					props.attributes.selectedDebate || props.attributes.debateCreated === false ? '' :	el(
							'button', {
								id:"setSelectedDebate",
								className: "edit-block-btn",
								onClick: setSelectedDebate
							},
							'Insert this poll'
						),
				)
			);

			return el(
						'div', {
							className: "sided-wp-plugin-wrapper"
						},
						el(
							'div', {
								className: "components-placeholder",
							},
							children
						)
					);
		},

		save: function(props) {
			var dId = props.attributes.selectedDebate;
			if (dId === null) {
				return null;
			}
			return '[sided-debate-embed debate-id="' + dId + '"]';
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);