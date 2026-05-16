import { Plugin, Command, ButtonView, View, LinkUI } from 'ckeditor5';

class InsertBookmarkCommand extends Command {
	execute( options = {} ) {
		const editor = this.editor;
		const bookmarkName = options.bookmarkName || 'bookmark';
		editor.model.change( writer => {
			const bookmarkElement = writer.createElement( 'bookmark', { id: bookmarkName } );
			editor.model.insertContent( bookmarkElement );
		} );
	}
}

class BookmarkDialogView extends View {
	constructor( locale, { label, placeholder } ) {
		super( locale );

		this.label = label;
		this.placeholder = placeholder;

		this.setTemplate( {
			tag: 'div',
			attributes: {
				class: 'ck-anchor-dialog'
			},
			children: [
				{
					tag: 'label',
					attributes: {
						class: 'ck-anchor-dialog__label'
					},
					children: [ this.label ]
				},
				{
					tag: 'input',
					attributes: {
						type: 'text',
						class: 'ck-anchor-dialog__input',
						placeholder: this.placeholder
					}
				}
			]
		} );
	}

	focus() {
		const input = this.element && this.element.querySelector( 'input' );
		if ( input ) {
			input.focus();
		}
	}

	getValue() {
		const input = this.element && this.element.querySelector( 'input' );
		return input ? input.value.trim() : '';
	}
}

export default class Bookmark extends Plugin {
	static get requires() {
		return [ LinkUI, 'Dialog' ];
	}

	init() {
		const editor = this.editor;

		// Schema
		editor.model.schema.register( 'bookmark', {
			allowWhere: '$text',
			isInline: true,
			isObject: true,
			inheritAllFrom: '$inlineObject',
			allowAttributes: [ 'id','class','href' ]
		} );

		// Conversion
		editor.conversion.for( 'downcast' ).elementToElement( {
			model: 'bookmark',
			view: ( modelElement, { writer } ) => {
				return writer.createEmptyElement( 'a', {
					id: modelElement.getAttribute( 'id' ),
					class: 'ck-anchor',
					href: '#'+modelElement.getAttribute( 'id' )
				} );
			}
		} );

		editor.conversion.for( 'upcast' ).elementToElement( {
			view: {
				name: 'a',
				classes: [ 'ck-anchor' ]
			},
			model: ( viewElement, { writer } ) => {
				if ( viewElement.hasAttribute( 'id' ) ) {
					return writer.createElement( 'bookmark', { 
						id: viewElement.getAttribute( 'id' ),
						class: viewElement.getAttribute( 'class' )
					} );
				}
			}
		} );

		// Commands
		editor.commands.add( 'insertBookmark', new InsertBookmarkCommand( editor ) );

		// Dialog views
		this._insertBookmarkView = new BookmarkDialogView( editor.locale, {
			label: editor.locale.t( 'Bookmark name' ),
			placeholder: editor.locale.t( '' )
		} );

		// Toolbar buttons
		editor.ui.componentFactory.add( 'insertBookmark', locale => {
			const button = new ButtonView( locale );
			button.set( {
				label: editor.locale.t( 'Insert Bookmark' ),
				icon: '<svg viewBox="0 0 14 16" xmlns="http://www.w3.org/2000/svg"><path d="M2 14.436V2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v12.436a.5.5 0 0 1-.819.385l-3.862-3.2a.5.5 0 0 0-.638 0l-3.862 3.2A.5.5 0 0 1 2 14.436"></path></svg>',
				//icon: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8zm-1-13h2v6H9V5zm0 8h2v2H9v-2z"/></svg>',
				tooltip: true
			} );

			button.on( 'execute', () => {
				const dialog = editor.plugins.get( 'Dialog' );
				const view = this._insertBookmarkView;

				dialog.show( {
					id: 'insertBookmark',
					title: editor.locale.t( 'Insert bookmark' ),
					content: view,
					actionButtons: [
						{
							type: 'cancel'
						},
						{
							type: 'submit',
							label: editor.locale.t( 'Insert' ),
							withText: true,
							onExecute: () => {
								const bookmarkName = view.getValue();
								if ( bookmarkName ) {
									editor.execute( 'insertBookmark', { bookmarkName } );
								}
								dialog.hide();
							}
						}
					],
					onShow: () => {
						view.focus();
					}
				} );
			} );

			return button;
		} );
	}
}

