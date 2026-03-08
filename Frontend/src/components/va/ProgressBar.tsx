"use client";

import { Progress } from "antd";

interface ProgressBarProps {
  percentage: number;
  size?: "small" | "default";
  showLabel?: boolean;
}

export default function ProgressBar({
  percentage,
  size = "default",
  showLabel = true,
}: ProgressBarProps) {
  const strokeColor = {
    "0%": "#26634B",
    "100%": "#0AEBD7",
  };

  return (
    <div className="flex items-center gap-2">
      <Progress
        type="line"
        percent={percentage ?? 0}
        strokeColor={strokeColor}
        format={() => showLabel ? `${percentage ?? 0}%` : null}
        size={size === "small" ? "small" : "default"}
      />
    </div>
  );
}
