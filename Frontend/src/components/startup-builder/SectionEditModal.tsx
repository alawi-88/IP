"use client";

import { useState, useEffect, useCallback } from "react";
import { Modal, message } from "antd";
import { FiSave, FiPlus, FiTrash2, FiChevronDown, FiChevronRight } from "react-icons/fi";
import axiosInstance from "@/axios";

interface SectionEditModalProps {
  open: boolean;
  onClose: () => void;
  section: any;
  ventureId: string | number;
  onSaved: () => void;
}

export default function SectionEditModal({
  open,
  onClose,
  section,
  ventureId,
  onSaved,
}: SectionEditModalProps) {
  const [jsonContent, setJsonContent] = useState("");
  const [saving, setSaving] = useState(false);
  const [jsonError, setJsonError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("visual");
  const [expandedPaths, setExpandedPaths] = useState<Set<string>>(new Set());

  useEffect(() => {
    if (section?.content) {
      const formatted = JSON.stringify(section.content, null, 2);
      setJsonContent(formatted);
      setJsonError(null);
      // Auto-expand top-level keys
      const topKeys = Object.keys(section.content);
      setExpandedPaths(new Set(topKeys));
    }
  }, [section]);

  const validateJson = useCallback((value: string) => {
    try {
      JSON.parse(value);
      setJsonError(null);
      return true;
    } catch (e: any) {
      setJsonError(e.message);
      return false;
    }
  }, []);

  const handleJsonChange = (value: string) => {
    setJsonContent(value);
    validateJson(value);
  };

  const handleSave = async () => {
    if (!validateJson(jsonContent)) {
      message.error("Invalid JSON content. Please fix errors before saving.");
      return;
    }

    setSaving(true);
    try {
      await axiosInstance.put(
        `/participants/ventures/${ventureId}/sections/${section.id}`,
        { content: JSON.parse(jsonContent) }
      );
      message.success("Section updated successfully");
      onSaved();
      onClose();
    } catch (error: any) {
      message.error(
        error?.response?.data?.message || "Failed to update section"
      );
    } finally {
      setSaving(false);
    }
  };

  const getParsedContent = useCallback(() => {
    try {
      return JSON.parse(jsonContent);
    } catch {
      return null;
    }
  }, [jsonContent]);

  const updateValueAtPath = useCallback(
    (path: string[], newValue: any) => {
      try {
        const parsed = JSON.parse(jsonContent);
        let obj = parsed;
        for (let i = 0; i < path.length - 1; i++) {
          const key = path[i];
          obj = typeof obj === "object" && obj !== null ? obj[key] : undefined;
          if (obj === undefined) return;
        }
        const lastKey = path[path.length - 1];
        obj[lastKey] = newValue;
        const updated = JSON.stringify(parsed, null, 2);
        setJsonContent(updated);
        setJsonError(null);
      } catch {
        setActiveTab("json");
      }
    },
    [jsonContent]
  );

  const deleteAtPath = useCallback(
    (path: string[]) => {
      try {
        const parsed = JSON.parse(jsonContent);
        let obj = parsed;
        for (let i = 0; i < path.length - 1; i++) {
          obj = obj[path[i]];
        }
        const lastKey = path[path.length - 1];
        if (Array.isArray(obj)) {
          obj.splice(Number(lastKey), 1);
        } else {
          delete obj[lastKey];
        }
        const updated = JSON.stringify(parsed, null, 2);
        setJsonContent(updated);
        setJsonError(null);
      } catch {
        setActiveTab("json");
      }
    },
    [jsonContent]
  );

  const addArrayItem = useCallback(
    (path: string[], template: any) => {
      try {
        const parsed = JSON.parse(jsonContent);
        let obj = parsed;
        for (const key of path) {
          obj = obj[key];
        }
        if (Array.isArray(obj)) {
          obj.push(template);
        }
        const updated = JSON.stringify(parsed, null, 2);
        setJsonContent(updated);
        setJsonError(null);
      } catch {
        setActiveTab("json");
      }
    },
    [jsonContent]
  );

  const toggleExpand = (path: string) => {
    setExpandedPaths((prev) => {
      const next = new Set(prev);
      if (next.has(path)) {
        next.delete(path);
      } else {
        next.add(path);
      }
      return next;
    });
  };

  const formatLabel = (key: string) =>
    key
      .replace(/_/g, " ")
      .replace(/-/g, " ")
      .replace(/\b\w/g, (c) => c.toUpperCase());

  // Render a visual editor for any value type recursively
  const renderValue = (
    value: any,
    path: string[],
    key: string,
    depth: number = 0
  ): React.ReactNode => {
    const pathStr = path.join(".");

    if (value === null || value === undefined) {
      return (
        <div key={pathStr} className="flex items-center gap-2" style={{ paddingLeft: depth * 16 }}>
          <span className="text-xs text-gray-400 italic">null</span>
        </div>
      );
    }

    if (Array.isArray(value)) {
      const isExpanded = expandedPaths.has(pathStr);
      const template =
        value.length > 0
          ? typeof value[0] === "object"
            ? Object.fromEntries(
                Object.keys(value[0]).map((k) => [
                  k,
                  typeof value[0][k] === "string"
                    ? ""
                    : typeof value[0][k] === "number"
                      ? 0
                      : typeof value[0][k] === "boolean"
                        ? false
                        : Array.isArray(value[0][k])
                          ? []
                          : "",
                ])
              )
            : typeof value[0] === "string"
              ? ""
              : 0
          : "";

      return (
        <div key={pathStr} className="mb-2" style={{ paddingLeft: depth > 0 ? 8 : 0 }}>
          <button
            onClick={() => toggleExpand(pathStr)}
            className="flex items-center gap-1.5 text-xs font-semibold text-gray-700 hover:text-[#25935F] transition-colors mb-1"
          >
            {isExpanded ? <FiChevronDown size={12} /> : <FiChevronRight size={12} />}
            <span className="uppercase tracking-wide">{formatLabel(key)}</span>
            <span className="text-gray-400 font-normal">({value.length} items)</span>
          </button>
          {isExpanded && (
            <div className="ml-2 border-l-2 border-gray-100 pl-3 space-y-2">
              {value.map((item: any, index: number) => {
                const itemPath = [...path, String(index)];
                const itemPathStr = itemPath.join(".");

                if (typeof item === "object" && item !== null && !Array.isArray(item)) {
                  const isItemExpanded = expandedPaths.has(itemPathStr);
                  const itemLabel =
                    item.name || item.title || item.label || item.key || `Item ${index + 1}`;
                  return (
                    <div key={itemPathStr} className="bg-gray-50 rounded-lg p-2.5 border border-gray-100">
                      <div className="flex items-center justify-between mb-1">
                        <button
                          onClick={() => toggleExpand(itemPathStr)}
                          className="flex items-center gap-1 text-xs font-medium text-gray-700 hover:text-[#25935F]"
                        >
                          {isItemExpanded ? <FiChevronDown size={11} /> : <FiChevronRight size={11} />}
                          <span>{itemLabel}</span>
                        </button>
                        <button
                          onClick={() => deleteAtPath(itemPath)}
                          className="p-1 text-gray-300 hover:text-red-500 transition-colors"
                          title="Remove item"
                        >
                          <FiTrash2 size={12} />
                        </button>
                      </div>
                      {isItemExpanded && (
                        <div className="space-y-2 mt-2">
                          {Object.entries(item).map(([k, v]) =>
                            renderValue(v, [...itemPath, k], k, depth + 2)
                          )}
                        </div>
                      )}
                    </div>
                  );
                }

                // Primitive array items
                return (
                  <div key={itemPathStr} className="flex items-center gap-2">
                    <input
                      type="text"
                      defaultValue={String(item)}
                      onBlur={(e) => {
                        const val =
                          typeof item === "number"
                            ? Number(e.target.value) || 0
                            : e.target.value;
                        updateValueAtPath(itemPath, val);
                      }}
                      className="flex-1 px-2.5 py-1.5 text-xs border border-gray-200 rounded-md focus:ring-1 focus:ring-[#25935F] focus:border-[#25935F] outline-none"
                    />
                    <button
                      onClick={() => deleteAtPath(itemPath)}
                      className="p-1 text-gray-300 hover:text-red-500 transition-colors"
                    >
                      <FiTrash2 size={12} />
                    </button>
                  </div>
                );
              })}
              <button
                onClick={() => addArrayItem(path, template)}
                className="flex items-center gap-1 text-xs text-[#25935F] hover:text-[#1f8753] font-medium mt-1"
              >
                <FiPlus size={12} />
                Add Item
              </button>
            </div>
          )}
        </div>
      );
    }

    if (typeof value === "object") {
      const isExpanded = expandedPaths.has(pathStr);
      return (
        <div key={pathStr} className="mb-2" style={{ paddingLeft: depth > 0 ? 8 : 0 }}>
          <button
            onClick={() => toggleExpand(pathStr)}
            className="flex items-center gap-1.5 text-xs font-semibold text-gray-700 hover:text-[#25935F] transition-colors mb-1"
          >
            {isExpanded ? <FiChevronDown size={12} /> : <FiChevronRight size={12} />}
            <span className="uppercase tracking-wide">{formatLabel(key)}</span>
          </button>
          {isExpanded && (
            <div className="ml-2 border-l-2 border-gray-100 pl-3 space-y-2">
              {Object.entries(value).map(([k, v]) =>
                renderValue(v, [...path, k], k, depth + 1)
              )}
            </div>
          )}
        </div>
      );
    }

    // Primitive values
    const isLong = typeof value === "string" && value.length > 120;
    const isBool = typeof value === "boolean";

    if (isBool) {
      return (
        <div key={pathStr} className="flex items-center gap-2 mb-1.5" style={{ paddingLeft: depth > 0 ? 8 : 0 }}>
          <label className="text-xs text-gray-500 min-w-[120px]">{formatLabel(key)}</label>
          <button
            onClick={() => updateValueAtPath(path, !value)}
            className={`relative w-9 h-5 rounded-full transition-colors ${
              value ? "bg-[#25935F]" : "bg-gray-300"
            }`}
          >
            <span
              className={`absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform ${
                value ? "translate-x-4" : ""
              }`}
            />
          </button>
          <span className="text-xs text-gray-400">{value ? "Yes" : "No"}</span>
        </div>
      );
    }

    return (
      <div key={pathStr} className="mb-1.5" style={{ paddingLeft: depth > 0 ? 8 : 0 }}>
        <label className="block text-xs text-gray-500 mb-0.5">{formatLabel(key)}</label>
        {isLong ? (
          <textarea
            defaultValue={String(value)}
            onBlur={(e) => {
              const val =
                typeof value === "number"
                  ? Number(e.target.value) || 0
                  : e.target.value;
              updateValueAtPath(path, val);
            }}
            className="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-md focus:ring-1 focus:ring-[#25935F] focus:border-[#25935F] outline-none resize-y min-h-[60px]"
            rows={3}
          />
        ) : (
          <input
            type={typeof value === "number" ? "number" : "text"}
            defaultValue={String(value)}
            onBlur={(e) => {
              const val =
                typeof value === "number"
                  ? Number(e.target.value) || 0
                  : e.target.value;
              updateValueAtPath(path, val);
            }}
            className="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-md focus:ring-1 focus:ring-[#25935F] focus:border-[#25935F] outline-none"
          />
        )}
      </div>
    );
  };

  const parsedContent = getParsedContent();
  const sectionTitle =
    section?.label_en ||
    section?.slug
      ?.replace(/-/g, " ")
      .replace(/_/g, " ")
      .replace(/\b\w/g, (c: string) => c.toUpperCase());

  return (
    <Modal
      open={open}
      onCancel={onClose}
      title={
        <div className="flex items-center gap-2">
          <span className="text-lg font-semibold">Edit: {sectionTitle}</span>
        </div>
      }
      footer={
        <div className="flex items-center justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleSave}
            disabled={saving || !!jsonError}
            className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#25935F] rounded-lg hover:bg-[#1f8753] transition-colors disabled:opacity-50"
          >
            <FiSave size={14} />
            {saving ? "Saving..." : "Save Changes"}
          </button>
        </div>
      }
      width={900}
      destroyOnClose
    >
      <div className="mt-2">
        <div className="flex border-b border-gray-200 mb-4">
          <button
            onClick={() => setActiveTab("visual")}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
              activeTab === "visual"
                ? "border-[#25935F] text-[#25935F]"
                : "border-transparent text-gray-500 hover:text-gray-700"
            }`}
          >
            Visual Editor
          </button>
          <button
            onClick={() => setActiveTab("json")}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
              activeTab === "json"
                ? "border-[#25935F] text-[#25935F]"
                : "border-transparent text-gray-500 hover:text-gray-700"
            }`}
          >
            JSON Editor
          </button>
        </div>

        {activeTab === "visual" && (
          <div className="max-h-[65vh] overflow-y-auto pr-2">
            {parsedContent && typeof parsedContent === "object" ? (
              <div className="space-y-2">
                {Object.entries(parsedContent).map(([key, value]) =>
                  renderValue(value, [key], key, 0)
                )}
              </div>
            ) : (
              <div className="text-center text-gray-400 py-6">
                <p className="text-sm">
                  Unable to parse content visually. Use the JSON Editor tab to
                  edit content directly.
                </p>
              </div>
            )}
          </div>
        )}

        {activeTab === "json" && (
          <div>
            <textarea
              value={jsonContent}
              onChange={(e) => handleJsonChange(e.target.value)}
              className={`w-full px-3 py-3 font-mono text-xs border rounded-lg focus:ring-1 focus:outline-none resize-y min-h-[400px] ${
                jsonError
                  ? "border-red-300 focus:ring-red-500 focus:border-red-500"
                  : "border-gray-200 focus:ring-[#25935F] focus:border-[#25935F]"
              }`}
              spellCheck={false}
            />
            {jsonError && (
              <p className="text-xs text-red-500 mt-1">
                JSON Error: {jsonError}
              </p>
            )}
          </div>
        )}
      </div>
    </Modal>
  );
}
