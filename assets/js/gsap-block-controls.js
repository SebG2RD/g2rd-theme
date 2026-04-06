/**
 * Contrôles d'animation GSAP pour les blocs
 *
 * Ce script gère les contrôles d'animation GSAP dans l'éditeur
 * de blocs WordPress.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

/*! For license information please see gsap-block-controls.js.LICENSE.txt */
(() => {
  "use strict";
  var e = {
      287: (e, t) => {
        var r = Symbol.for("react.element"),
          o = (Symbol.for("react.portal"), Symbol.for("react.fragment")),
          n =
            (Symbol.for("react.strict_mode"),
            Symbol.for("react.profiler"),
            Symbol.for("react.provider"),
            Symbol.for("react.context"),
            Symbol.for("react.forward_ref"),
            Symbol.for("react.suspense"),
            Symbol.for("react.memo"),
            Symbol.for("react.lazy"),
            Symbol.iterator,
            {
              isMounted: function () {
                return !1;
              },
              enqueueForceUpdate: function () {},
              enqueueReplaceState: function () {},
              enqueueSetState: function () {},
            }),
          a = Object.assign,
          i = {};
        function s(e, t, r) {
          (this.props = e),
            (this.context = t),
            (this.refs = i),
            (this.updater = r || n);
        }
        function c() {}
        function l(e, t, r) {
          (this.props = e),
            (this.context = t),
            (this.refs = i),
            (this.updater = r || n);
        }
        (s.prototype.isReactComponent = {}),
          (s.prototype.setState = function (e, t) {
            if ("object" != typeof e && "function" != typeof e && null != e)
              throw Error(
                "setState(...): takes an object of state variables to update or a function which returns an object of state variables."
              );
            this.updater.enqueueSetState(this, e, t, "setState");
          }),
          (s.prototype.forceUpdate = function (e) {
            this.updater.enqueueForceUpdate(this, e, "forceUpdate");
          }),
          (c.prototype = s.prototype);
        var p = (l.prototype = new c());
        (p.constructor = l), a(p, s.prototype), (p.isPureReactComponent = !0);
        Array.isArray;
        var u = Object.prototype.hasOwnProperty,
          f = { current: null },
          m = { key: !0, ref: !0, __self: !0, __source: !0 };
        (t.Fragment = o),
          (t.createElement = function (e, t, o) {
            var n,
              a = {},
              i = null,
              s = null;
            if (null != t)
              for (n in (void 0 !== t.ref && (s = t.ref),
              void 0 !== t.key && (i = "" + t.key),
              t))
                u.call(t, n) && !m.hasOwnProperty(n) && (a[n] = t[n]);
            var c = arguments.length - 2;
            if (1 === c) a.children = o;
            else if (1 < c) {
              for (var l = Array(c), p = 0; p < c; p++) l[p] = arguments[p + 2];
              a.children = l;
            }
            if (e && e.defaultProps)
              for (n in (c = e.defaultProps)) void 0 === a[n] && (a[n] = c[n]);
            return {
              $$typeof: r,
              type: e,
              key: i,
              ref: s,
              props: a,
              _owner: f.current,
            };
          });
      },
      540: (e, t, r) => {
        e.exports = r(287);
      },
    },
    t = {},
    r = (function r(o) {
      var n = t[o];
      if (void 0 !== n) return n.exports;
      var a = (t[o] = { exports: {} });
      return e[o](a, a.exports, r), a.exports;
    })(540);
  const { registerPlugin: o } = wp.plugins,
    { PanelBody: n, SelectControl: a } = wp.components,
    { useSelect: i, dispatch: s } = wp.data,
    { PluginBlockSettingsMenuItem: c } = wp.blockEditor,
    { InspectorControls: l } = wp.blockEditor,
    { createHigherOrderComponent: p } = wp.compose,
    u = [
      // Blocs de mise en page
      "core/group",
      "core/columns",
      "core/column",
      "core/cover",
      "core/media-text",
      "core/spacer",
      "core/separator",
      "core/buttons",
      "core/button",
      "core/navigation",
      "core/navigation-link",
      "core/social-links",
      "core/social-link",
      "core/site-logo",
      "core/site-title",
      "core/site-tagline",
      "core/query",
      "core/post-template",
      "core/query-title",
      "core/query-pagination",
      "core/query-pagination-next",
      "core/query-pagination-previous",
      "core/query-pagination-numbers",
      "core/post-title",
      "core/post-content",
      "core/post-excerpt",
      "core/post-featured-image",
      "core/post-date",
      "core/post-terms",
      "core/post-navigation-link",
      "core/read-more",
      "core/comments",
      "core/comments-title",
      "core/comments-query-loop",
      "core/comments-pagination",
      "core/comments-pagination-next",
      "core/comments-pagination-previous",
      "core/comments-pagination-numbers",
      "core/comment-template",
      "core/comment-author-name",
      "core/comment-content",
      "core/comment-date",
      "core/comment-edit-link",
      "core/comment-reply-link",
      "core/comments-query-loop",
      "core/comments-pagination",
      "core/comments-pagination-next",
      "core/comments-pagination-previous",
      "core/comments-pagination-numbers",
      "core/comment-template",
      "core/comment-author-name",
      "core/comment-content",
      "core/comment-date",
      "core/comment-edit-link",
      "core/comment-reply-link",
      // Blocs de contenu
      "core/paragraph",
      "core/heading",
      "core/list",
      "core/list-item",
      "core/quote",
      "core/pullquote",
      "core/code",
      "core/preformatted",
      "core/verse",
      "core/table",
      "core/table-of-contents",
      "core/details",
      "core/details-summary",
      "core/details-content",
      // Blocs multimédia
      "core/image",
      "core/gallery",
      "core/audio",
      "core/video",
      "core/file",
      "core/embed",
      "core/calendar",
      "core/rss",
      "core/search",
      "core/tag-cloud",
      "core/latest-posts",
      "core/latest-comments",
      "core/archives",
      "core/categories",
      "core/latest-categories",
      "core/avatar",
      "core/post-author",
      "core/post-author-biography",
      "core/term-description",
      "core/query-no-results",
      "core/query-pagination",
      "core/query-pagination-next",
      "core/query-pagination-previous",
      "core/query-pagination-numbers",
      "core/query-title",
      "core/query-loop",
      "core/post-template",
      "core/post-title",
      "core/post-content",
      "core/post-excerpt",
      "core/post-featured-image",
      "core/post-date",
      "core/post-terms",
      "core/post-navigation-link",
      "core/read-more",
      "core/comments",
      "core/comments-title",
      "core/comments-query-loop",
      "core/comments-pagination",
      "core/comments-pagination-next",
      "core/comments-pagination-previous",
      "core/comments-pagination-numbers",
      "core/comment-template",
      "core/comment-author-name",
      "core/comment-content",
      "core/comment-date",
      "core/comment-edit-link",
      "core/comment-reply-link",
      // Blocs de widgets
      "core/widget-area",
      "core/widget-group",
      "core/widget",
      "core/widget-area",
      "core/widget-group",
      "core/widget",
      // Blocs de navigation
      "core/navigation",
      "core/navigation-link",
      "core/navigation-submenu",
      "core/page-list",
      "core/home-link",
      "core/loginout",
      "core/social-links",
      "core/social-link",
      "core/site-logo",
      "core/site-title",
      "core/site-tagline",
      // Blocs de requête
      "core/query",
      "core/query-title",
      "core/query-pagination",
      "core/query-pagination-next",
      "core/query-pagination-previous",
      "core/query-pagination-numbers",
      "core/post-template",
      "core/post-title",
      "core/post-content",
      "core/post-excerpt",
      "core/post-featured-image",
      "core/post-date",
      "core/post-terms",
      "core/post-navigation-link",
      "core/read-more",
      // Blocs de commentaires
      "core/comments",
      "core/comments-title",
      "core/comments-query-loop",
      "core/comments-pagination",
      "core/comments-pagination-next",
      "core/comments-pagination-previous",
      "core/comments-pagination-numbers",
      "core/comment-template",
      "core/comment-author-name",
      "core/comment-content",
      "core/comment-date",
      "core/comment-edit-link",
      "core/comment-reply-link",
    ],
    f = p(
      (e) => (t) => {
        if (!u.includes(t.name)) return (0, r.createElement)(e, { ...t });
        const { attributes: o, setAttributes: i, clientId: s } = t,
          c = o?.className?.split(" ") || [],
          p = c.find((e) => e.startsWith("gsap-")) || "",
          f = [
            { label: "Aucune", value: "" },
            ...Object.entries(window.gsapBlockData.animations).map(
              ([e, t]) => ({
                label: t,
                value: `gsap-${e.replace(/([A-Z])/g, "-$1").toLowerCase()}`,
              })
            ),
          ];
        return (0, r.createElement)(
          r.Fragment,
          null,
          (0, r.createElement)(e, { ...t }),
          (0, r.createElement)(
            l,
            null,
            (0, r.createElement)(
              n,
              { title: "Animation GSAP", initialOpen: !0 },
              (0, r.createElement)(a, {
                label: "Choisir une animation",
                value: p,
                options: f,
                onChange: (e) => {
                  const t = c.filter((e) => !e.startsWith("gsap-"));
                  e && t.push(e);
                  const r = t.join(" ").trim() || void 0;
                  i({ className: r });
                },
                __next40pxDefaultSize: !0,
                __nextHasNoMarginBottom: !0,
              }),
              (0, r.createElement)(
                "p",
                {
                  style: {
                    marginTop: "10px",
                    fontSize: "12px",
                    fontStyle: "italic",
                  },
                },
                "L'animation sera activée sur la page publiée."
              )
            )
          )
        );
      },
      "withGsapAnimations"
    );
  wp.hooks.addFilter("editor.BlockEdit", "gsap-animations/with-animations", f),
    o("gsap-animation-control", { render: () => null, icon: "move" });
})();
