(()=>{"use strict";const e=window.wp.blocks;function t(){return t=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var o=arguments[t];for(var r in o)Object.prototype.hasOwnProperty.call(o,r)&&(e[r]=o[r])}return e},t.apply(this,arguments)}const o=window.wp.element,r=window.wp.i18n,l=window.wp.data,n=window.wp.coreData,p=window.wp.blockEditor,s=window.wp.components,a=JSON.parse('{"u2":"pmpro/checkout-page"}');(0,e.registerBlockType)(a.u2,{icon:{background:"#FFFFFF",foreground:"#658B24",src:"list-view"},edit:function({attributes:e,setAttributes:a}){const i=(0,p.useBlockProps)({}),c=(0,l.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[m,u]=(0,n.useEntityProp)("postType",c,"meta"),d=m.pmpro_default_level;return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(p.InspectorControls,null,(0,o.createElement)(s.PanelBody,null,(0,o.createElement)(s.SelectControl,{label:(0,r.__)("Membership Level","paid-memberships-pro"),help:(0,r.__)("Choose a default level for Membership Checkout.","paid-memberships-pro"),value:d,onChange:e=>{u({...m,pmpro_default_level:e})},options:[""].concat(window.pmpro.all_level_values_and_labels)}))),(0,o.createElement)("div",t({className:"pmpro-block-element"},i),(0,o.createElement)("span",{className:"pmpro-block-title"},(0,r.__)("Paid Memberships Pro","paid-memberships-pro")),(0,o.createElement)("span",{className:"pmpro-block-subtitle"}," ",(0,r.__)("Membership Checkout Form","paid-memberships-pro"))))}})})();