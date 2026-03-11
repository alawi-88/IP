"use client";

import { useState, useEffect, useCallback } from "react";
import { Modal, message, Tabs } from "antd";
import { FiSave, FiX } from "react-icons/fi";
import { HiOutlineSparkles } from "react-icons/hi2";
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

  useEffect(() => {
    if (section?.content) {
      const formatted = JSON.stringify(section.content, null, 2);
      setJsonContent(formatted);
      setJsonError(null);
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
        { content: jsonContent }
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

  // Extract simple text fields from content for visual editing
  const getEditableFields = (content: any): Array<{ path: string; label: string; value: string; type: "text" | "textarea" }> => {
    if (!content || typeof content !== "object") return [];
    const fields: Array<{ path: string; label: string; value: string; type: "text" | "textarea" }> = [];

    const traverse = (obj: any, prefix: string) => {
      for (const [key, val] of Object.entries(obj)) {
        const path = prefix ? `${prefix}.${key}` : key;
        if (typeof val === "string") {
          const isLong = val.length > 100;
          fields.push({
            path,
            label: key
              .replace(/_/g, " ")
              .replace(/\b\w/g, (c) => c.toUpperCase()),
            value: val,
            type: isLong ? "textarea" : "text",
          });
        } else if (typeof val === "number" || typeof val === "boolean") {
          fields.push({
            path,
            label: key
              .replace(/_/g, " ")
              .replace(/\b\w/g, (c) => c.toUpperCase()),
            value: String(val),
            type: "text",
          });
        }
      }
    };

    traverse(content, "");
    return fields.slice(0, 30); // Limit to 30 top-level fields
  };

  const updateFieldInJson = (path: string, newValue: string) => {
    try {
      const parsed = JSON.parse(jsonContent);
      const keys = path.split(".");
      let obj = parsed;
      for (let i = 0; i < keys.length - 1; i++) {
        obj = obj[keys[i]];
      }
      const lastKey = keys[keys.length - 1];
      // Try to preserve type
      const original = obj[lastKey];
      if (typeof original === "number") {
        obj[lastKey] = Number(newValue) || 0;
      } else if (typeof original === "boolean") {
        obj[lastKey] = newValue === "true";
      } else {
        obj[lastKey] = newValue;
      }
      const updated = JSON.stringify(parsed, null, 2);
      setJsonContent(updated);
      setJsonError(null);
    } catch (e) {
      // Fallback to raw JSON editor
      setActiveTab("json");
    }
  };

  const editableFields = section?.content
    ? getEditableFields(section.content)
    : [];
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
      width={800}
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
          <div className="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
            {editableFields.length > 0 ? (
              editableFields.map((field) => (
                <div key={field.path}>
                  <label className="block text-xs font-medium text-gray-600 mb-1">
                    {field.label}
                  </label>
                  {field.type === "textarea" ? (
                    <textarea
                      defaultValue={field.value}
                      onBlur={(e) =>
                        updateFieldInJson(field.path, e.target.value)
                      }
                      className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#25935F] focus:border-[#25935F] outline-none resize-y min-h-[80px]"
                      rows={3}
                    />
                  ) : (
                    <input
                      type="text"
                      defaultValue={field.value}
                      onBlur={(e) =>
                        updateFieldInJson(field.path, e.target.value)
                      }
                      className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#25935F] focus:border-[#25935F] outline-none"
                    />
                  )}
                </div>
              ))
            ) : (
              <div className="text-center text-gray-400 py-6">
                <p className="text-sm">
                  No editable fields detected. Use the JSON Editor tab to edit
                  content directly.
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
