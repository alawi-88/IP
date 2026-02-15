"use client";
import React from "react";
import { useEffect, useMemo, useRef, useState } from "react";
import { FaRegUser, FaRegFileAlt } from "react-icons/fa";
import { MdDownload } from "react-icons/md";
import { IoClose } from "react-icons/io5";
import { GrAttachment } from "react-icons/gr";
import { VscSend } from "react-icons/vsc";
import { useLocale, useTranslations } from "next-intl";
import { Button, message, Spin } from "antd";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axiosInstance, { APIError } from "@/axios";
import dayjs from "dayjs";
import Empty from "./Empty";

type CommentsProps = {
  type: string;
  typeId: string;
  enableReply?: string | boolean;
  hasComments?: boolean;
};

export const CommentItem = React.memo(
  ({ comment, t }: { comment: any; t: any }) => {
    return (
      <div
        className="comment-item space-y-3"
        key={comment.id}
        data-comment-id={comment.id}
        data-live={`${comment?.clientComment || false}`}
      >
        <div className="item-head flex items-start gap-x-2">
          <div className="item-avatar">
            {comment.author_type === "admin" ? (
              <div className="flex items-center justify-center font-bold w-12 h-12 rounded-full bg-[#E1F7F6] text-[#033F3D] text-sm">
                AD
              </div>
            ) : (
              <div className="flex items-center justify-center font-bold w-12 h-12 rounded-full bg-primary">
                <FaRegUser className="text-white w-4 h-4" />
              </div>
            )}
          </div>
          <div className="item-info">
            <p className="info-name">
              <span className="text-sm font-bold me-2">
                {comment.author_type === "admin"
                  ? comment.user?.name || comment.author?.name
                  : comment.author?.name}
              </span>
              {comment.author_type === "admin" && (
                <span className="inline-block text-sm py-1 px-2 rounded-lg font-medium border bg-[#E1F7F6] text-[#08BCB8] border-[#CEF2F1]">
                  {t("admin")}
                </span>
              )}
            </p>
            <p className="info-data text-[#626262] text-xs font-medium mt-1">
              {dayjs(comment.created_at).format("D/MM/YYYY  h:mm A")}
            </p>
          </div>
        </div>
        <div
          className="item-msg text-base font-medium"
          dangerouslySetInnerHTML={{ __html: comment.comment }}
        ></div>
        {comment?.attachments?.length > 0 &&
          comment.attachments.map((file: any, index: number) => (
            <div
              className="item-attachments space-y-2"
              key={`comment-${comment.id}-file-${index}`}
            >
              <div className="flex items-center bg-[#F6F7F9] px-4 py-3 rounded-xl max-w-[450px]">
                <span className="file-icon">
                  <FaRegFileAlt className="w-6 h-6" />
                </span>
                <span className="file-name text-sm font-medium overflow-hidden whitespace-nowrap px-3 flex-auto text-ellipsis">
                  {file?.split("comments/")[1] || file}
                </span>
                <a
                  className="file-link text-primary"
                  href={file}
                  target="_blank"
                  aria-label="download file"
                  download
                >
                  <MdDownload className="w-6 h-6" />
                </a>
              </div>
            </div>
          ))}
      </div>
    );
  }
);

function Comments({
  type = "",
  typeId,
  enableReply,
  hasComments,
}: CommentsProps) {
  const t = useTranslations();
  const locale = useLocale();
  const queryClient = useQueryClient();
  const { id, projectId } = useParams<{ id: string; projectId: string }>();
  const [messageApi, contextHolder] = message.useMessage();
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [canReply, setCanReply] = useState(enableReply);
  const [comment, setComment] = useState("");
  const [canSend, setCanSend] = useState(false);
  const [files, setFiles] = useState<File[]>([]);

  // Auto resize textarea
  const handleInput = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const textarea = textareaRef.current;
    // setComment(e.target.value);
    if (textarea) {
      textarea.style.height = "auto";
      textarea.style.height = Math.min(textarea.scrollHeight, 150) + "px";

      // handle uncontrolled textarea
      const newCanSend = textarea.value.trim().length > 0;
      if (newCanSend !== canSend) {
        setCanSend(newCanSend);
      }
    }
  };

  // Handle file upload
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files);

      const validFiles: File[] = [];

      newFiles.forEach((file) => {
        const isValid = file.size <= 5 * 1024 * 1024;
        if (isValid) {
          validFiles.push(file);
        } else {
          messageApi.error(`${file.name}: ${t("invalid-file")}`);
        }
      });

      setFiles((prev) => [...prev, ...validFiles]);

      e.target.value = "";
    }
  };

  // Remove file from list
  const removeFile = (index: number) => {
    setFiles((prev) => prev.filter((_, i) => i !== index));
  };

  // Get comments
  const {
    data: comments,
    isLoading,
    isFetching,
  } = useQuery({
    queryKey: ["comments", typeId, type],
    queryFn: async () => {
      try {
        const response = await axiosInstance.get(
          `/participants/${type}/${typeId}/comments`
        );

        return response?.data;
      } catch (error) {
        console.log(error);
        return null;
      }
    },
    // refetchInterval: 5000,
  });

  // mark comment as read
  const markCommentAsRead = useMutation({
    mutationFn: async () => {
      const response = await axiosInstance.post(
        `/participants/${type}/${typeId}/comments/mark-read`
      );
      return response.data;
    },
    onSuccess: () => {
      if (type === "projects") {
        queryClient.invalidateQueries({
          queryKey: ["projects", id],
        });
      }
      if (type === "applications") {
        queryClient.invalidateQueries({
          queryKey: ["my-competitions"],
        });
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        console.log(error.response.data.message);
      }
    },
  });

  // Post comment
  const { mutate, isPending } = useMutation({
    mutationFn: async () => {
      const formData = new FormData();
      const commentValue = textareaRef.current?.value || "";
      formData.append("comment", commentValue);
      files.forEach((file, index) =>
        formData.append(`attachments[${index}]`, file)
      );

      const response = await axiosInstance.post(
        `/participants/${type}/${typeId}/comments`,
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
        }
      );
      return response.data;
    },
    onSuccess: (newComment) => {
      queryClient.setQueryData(
        ["comments", typeId, type],
        (old: any[] = []) => {
          return [...old, { ...newComment, clientComment: true }];
        }
      );
      queryClient.invalidateQueries({
        queryKey: ["comments", typeId, type],
      });
      // setComment("");
      setCanSend(false);
      setFiles([]);
      if (textareaRef.current) {
        textareaRef.current.value = "";
        textareaRef.current.style.height = "auto";
      }
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
      setTimeout(() => {
        const mainEl = document.querySelector("main");
        if (mainEl) {
          mainEl.scrollTo({
            top: mainEl.scrollHeight,
            behavior: "smooth",
          });
        }
      }, 100);
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(error.response.data.message);
      }
      if (error.response.status === 403) {
        setCanReply(false);
      }
    },
  });

  // enforce dayjs update lang
  dayjs.locale(locale);

  //  mark comment as read on mount
  useEffect(() => {
    markCommentAsRead.mutate();
  }, []);

  return (
    <div className="dashboard-card gap-y-8">
      <h2 className="text-2xl text-primary-900 font-bold">{t("comments")}</h2>
      <div className="comments-list space-y-8">
        {isLoading ? (
          <Spin className="flex justify-center w-full" />
        ) : comments?.length ? (
          comments?.map((comment: any) => (
            <CommentItem key={comment.id} comment={comment} t={t} />
          ))
        ) : (
          <Empty description={t("no-comments-found")} />
        )}
      </div>
      {canReply && comments?.length > 0 && (
        <div className="reply-area flex gap-x-2 sm:gap-x-3">
          <div className="replay-avatar">
            <div className="flex items-center justify-center font-bold w-8 h-8 sm:w-12 sm:h-12 rounded-full bg-primary">
              <FaRegUser className="text-white w-3 h-3 sm:w-4 sm:h-4" />
            </div>
          </div>
          <div className="reply-box p-3 space-y-4 rounded-lg border border-[#DEE1E6] max-w-[650px] w-[calc(100%-2.5rem)] sm:w-[calc(100%-3.5rem)]">
            <div className="reply-input flex">
              <textarea
                ref={textareaRef}
                defaultValue={""}
                className="w-full text-base font-medium outline-none resize-none overflow-y-auto min-h-[26px] bg-[transparent]"
                aria-label={t("write-comment")}
                id="comment"
                placeholder={`${t("write-comment")}...`}
                rows={1}
                onInput={handleInput}
                maxLength={500}
              ></textarea>
            </div>

            {/* Attachments */}
            <div className="reply-attachments space-y-2">
              {files.map((file, index) => (
                <div
                  key={index}
                  className="flex items-center bg-[#F6F7F9] px-4 py-3 rounded-xl max-w-[450px]"
                >
                  <span className="file-icon">
                    <FaRegFileAlt className="w-6 h-6" />
                  </span>
                  <span className="file-name text-sm font-medium overflow-hidden whitespace-nowrap px-3 flex-auto text-ellipsis">
                    {file.name}
                  </span>
                  <button
                    type="button"
                    className="file-link min-w-auto p-0 hover:text-primary transition"
                    onClick={() => removeFile(index)}
                  >
                    <IoClose className="w-6 h-6" />
                  </button>
                </div>
              ))}
            </div>

            <div className="reply-actions flex items-center justify-between gap-4">
              <div className="upload-action">
                <label
                  className="cursor-pointer transition-all hover:text-primary"
                  htmlFor="uploadInput"
                >
                  <GrAttachment className="w-6 h-6" />
                </label>
                <input
                  ref={fileInputRef}
                  type="file"
                  id="uploadInput"
                  hidden
                  multiple
                  accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                  onChange={handleFileChange}
                />
              </div>
              <Button
                id="sendBtn"
                className="p-0 min-w-auto !border-[transparent] !bg-[transparent] w-6 h-6"
                onClick={() => mutate()}
                disabled={!canSend}
                loading={isPending}
              >
                <VscSend className="w-full h-full rtl:-scale-x-100" />
              </Button>
            </div>
          </div>
        </div>
      )}

      {contextHolder}
    </div>
  );
}

export default Comments;
