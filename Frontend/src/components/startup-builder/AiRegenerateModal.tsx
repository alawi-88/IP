"use client";

import { useState } from "react";
import { Modal, message } from "antd";
import { HiOutlineSparkles } from "react-icons/hi2";
import axiosInstance from "@/axios";

interface AiRegenerateModalProps {
  open: boolean;
  onClose: () => void;
  section: any;
  ventureId: string | number;
  onRegenerated: () => void;
}

export default function AiRegenerateModal({
  open,
  onClose,
  section,
  ventureId,
  onRegenerated,
}: AiRegenerateModalProps) {
  const [customPrompt, setCustomPrompt] = useState("");
  const [regenerating, setRegenerating] = useState(false);

  const sectionTitle =
    section?.label_en ||
    section?.slug
      ?.replace(/-/g, " ")
      .replace(/_/g, " ")
      .replace(/\b\w/g, (c: string) => c.toUpperCase());

  const handleRegenerate = async () => {
    setRegenerating(true);
    try {
      await axiosInstance.post(
        `/participants/ventures/${ventureId}/sections/${section.id}/regenerate`,
        customPrompt ? { custom_prompt: customPrompt } : {}
      );
      message.success("Section is being regenerated...");
      onRegenerated();
      onClose();
      setCustomPrompt("");
    } catch (error: any) {
      message.error(
        error?.response?.data?.message || "Failed to regenerate section"
      );
    } finally {
      setRegenerating(false);
    }
  };

  const suggestions = [
    "Make it more detailed and data-driven",
    "Focus on the Saudi Arabian market",
    "Add more specific financial figures",
    "Make it more concise and actionable",
    "Include competitive advantages",
    "Add industry benchmarks and statistics",
  ];

  return (
    <Modal
      open={open}
      onCancel={onClose}
      title={
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
            <HiOutlineSparkles className="text-purple-600" size={18} />
          </div>
          <div>
            <span className="text-lg font-semibold">AI Regenerate</span>
            <p className="text-xs text-gray-500 font-normal">{sectionTitle}</p>
          </div>
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
            onClick={handleRegenerate}
            disabled={regenerating}
            className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50"
          >
            <HiOutlineSparkles size={14} />
            {regenerating ? "Regenerating..." : "Regenerate with AI"}
          </button>
        </div>
      }
      width={600}
      destroyOnClose
    >
      <div className="mt-3 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Custom Instructions (Optional)
          </label>
          <textarea
            value={customPrompt}
            onChange={(e) => setCustomPrompt(e.target.value)}
            placeholder="Add specific instructions for the AI to follow when regenerating this section... Leave empty for default generation."
            className="w-full px-3 py-3 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none resize-y min-h-[100px]"
            rows={4}
          />
        </div>

        <div>
          <p className="text-xs font-medium text-gray-500 mb-2">
            Quick suggestions:
          </p>
          <div className="flex flex-wrap gap-2">
            {suggestions.map((suggestion) => (
              <button
                key={suggestion}
                onClick={() =>
                  setCustomPrompt((prev) =>
                    prev ? `${prev}. ${suggestion}` : suggestion
                  )
                }
                className="px-3 py-1.5 text-xs bg-purple-50 text-purple-700 rounded-full hover:bg-purple-100 transition-colors border border-purple-200"
              >
                {suggestion}
              </button>
            ))}
          </div>
        </div>

        <div className="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
          <p className="text-xs text-amber-700">
            <strong>Note:</strong> Regenerating will replace the current content
            with new AI-generated content. Your current content will be saved as
            a version.
          </p>
        </div>
      </div>
    </Modal>
  );
}
