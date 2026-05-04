/**
 * G2RD Map — extension saisie d'adresse postale avec autocomplete Nominatim
 * Enregistré comme second editorScript dans block.json
 */
(function (wp) {
  if (!wp) return;

  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { Fragment, createElement, useState, useRef, useEffect } = wp.element;
  const { InspectorControls } = wp.blockEditor;
  const { PanelBody, ToggleControl, TextControl, Spinner, Button } = wp.components;
  const { __ } = wp.i18n;

  // ── Ajout des attributs adresse au bloc g2rd/map ────────────────────────────
  addFilter(
    "blocks.registerBlockType",
    "g2rd/map-address-attrs",
    (settings, name) => {
      if (name !== "g2rd/map") return settings;
      return {
        ...settings,
        attributes: {
          ...settings.attributes,
          useAddress: { type: "boolean", default: false },
          address: { type: "string", default: "" },
        },
      };
    }
  );

  // ── Composant d'autocomplétion d'adresse ────────────────────────────────────
  function AddressAutocomplete({ value, onChange, onSelect }) {
    const [query, setQuery] = useState(value || "");
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);
    const debounceRef = useRef(null);
    const wrapperRef = useRef(null);

    // Fermer le dropdown en cliquant dehors
    useEffect(() => {
      const handleClickOutside = (e) => {
        if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
          setShowDropdown(false);
        }
      };
      document.addEventListener("mousedown", handleClickOutside);
      return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const handleInput = (val) => {
      setQuery(val);
      onChange(val);
      clearTimeout(debounceRef.current);

      if (val.trim().length < 3) {
        setSuggestions([]);
        setShowDropdown(false);
        return;
      }

      setLoading(true);
      debounceRef.current = setTimeout(async () => {
        try {
          const resp = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&limit=6&addressdetails=1`,
            { headers: { "Accept-Language": document.documentElement.lang || "fr" } }
          );
          const data = await resp.json();
          setSuggestions(data);
          setShowDropdown(data.length > 0);
        } catch (_) {
          setSuggestions([]);
        } finally {
          setLoading(false);
        }
      }, 400);
    };

    const handleSelect = (suggestion) => {
      const displayName = suggestion.display_name;
      setQuery(displayName);
      setSuggestions([]);
      setShowDropdown(false);
      onSelect({
        address: displayName,
        lat: parseFloat(suggestion.lat),
        lng: parseFloat(suggestion.lon),
      });
    };

    return createElement(
      "div",
      { ref: wrapperRef, style: { position: "relative" } },
      createElement(TextControl, {
        label: __("Adresse postale", "g2rd"),
        value: query,
        onChange: handleInput,
        placeholder: __("Ex : 1 Rue de la Paix, Paris…", "g2rd"),
        help: __("Saisissez au moins 3 caractères pour voir les suggestions.", "g2rd"),
        __nextHasNoMarginBottom: true,
      }),
      loading && createElement(
        "div",
        { style: { display: "flex", alignItems: "center", gap: "8px", marginTop: "4px", fontSize: "12px", color: "#757575" } },
        createElement(Spinner),
        __("Recherche…", "g2rd")
      ),
      showDropdown && createElement(
        "ul",
        {
          style: {
            background: "#fff",
            border: "1px solid #ddd",
            borderRadius: "4px",
            boxShadow: "0 4px 12px rgba(0,0,0,.12)",
            listStyle: "none",
            margin: "2px 0 0",
            maxHeight: "220px",
            overflowY: "auto",
            padding: 0,
            position: "absolute",
            width: "100%",
            zIndex: 9999,
          },
        },
        suggestions.map((s, i) =>
          createElement(
            "li",
            {
              key: i,
              role: "option",
              onClick: () => handleSelect(s),
              style: {
                borderBottom: "1px solid #f0f0f0",
                cursor: "pointer",
                fontSize: "12px",
                lineHeight: "1.4",
                padding: "8px 12px",
              },
              onMouseEnter: (e) => (e.currentTarget.style.background = "#f0f6ff"),
              onMouseLeave: (e) => (e.currentTarget.style.background = "transparent"),
            },
            s.display_name
          )
        )
      )
    );
  }

  // ── Injection du panneau dans l'inspector du bloc g2rd/map ──────────────────
  const withMapAddressControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
      if (props.name !== "g2rd/map") {
        return createElement(BlockEdit, props);
      }

      const { attributes, setAttributes } = props;
      const { useAddress, address, centerLat, centerLng } = attributes;

      return createElement(
        Fragment,
        null,
        createElement(BlockEdit, props),
        createElement(
          InspectorControls,
          null,
          createElement(
            PanelBody,
            { title: __("Adresse postale", "g2rd"), initialOpen: false },
            createElement(ToggleControl, {
              label: __("Utiliser une adresse postale", "g2rd"),
              help: useAddress
                ? __("Mode adresse — les coordonnées GPS seront mises à jour automatiquement.", "g2rd")
                : __("Mode coordonnées GPS.", "g2rd"),
              checked: useAddress,
              onChange: (v) => setAttributes({ useAddress: v }),
              __nextHasNoMarginBottom: true,
            }),
            useAddress &&
              createElement(AddressAutocomplete, {
                value: address,
                onChange: (val) => setAttributes({ address: val }),
                onSelect: ({ address: addr, lat, lng }) => {
                  setAttributes({ address: addr, centerLat: lat, centerLng: lng });
                },
              }),
            useAddress && centerLat && createElement(
              "p",
              { style: { fontSize: "11px", color: "#757575", marginTop: "8px" } },
              `📍 ${centerLat.toFixed(5)}, ${centerLng.toFixed(5)}`
            )
          )
        )
      );
    };
  }, "withMapAddressControls");

  addFilter(
    "editor.BlockEdit",
    "g2rd/map-address-controls",
    withMapAddressControls
  );
})(window.wp);
