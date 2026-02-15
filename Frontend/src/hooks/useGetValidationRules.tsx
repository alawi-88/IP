import { useLocale, useTranslations } from "next-intl";
import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import { Field } from "@/lib/interfaces";

dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

export function useGetValidationRules() {
  const t = useTranslations();
  const locale = useLocale();

  function getValidationRules(field: Field, isDraft?: boolean) {
    const rules = [];

    if (field.required && !isDraft) {
      rules.push({ required: true });
    }

    // if (field.type === "team") {
    //   rules.push({
    //     validator: async (_: any, values: any) => {
    //       if (!values || !values.length) return Promise.resolve();

    //       const wrongIds = values?.filter(
    //         (id: string) => isNaN(Number(id)) || id.length !== 6
    //       );

    //       if (wrongIds.length > 0) {
    //         return Promise.reject(
    //           wrongIds?.map(
    //             (id: string) =>
    //               `${t(
    //                 "the-entered-value-must-be-a-number-and-consist-of-6-digits"
    //               )} "${id}"`
    //           )
    //         );
    //       }

    //       return Promise.resolve();
    //     },
    //   });
    // }

    if (
      field.type === "checkbox" &&
      field?.mandatory_options?.indices?.length
    ) {
      const requiredIds = field.mandatory_options.indices;

      rules.push({
        validator: (_: any, value: any[] = []) => {
          if (!value.length) return Promise.resolve();

          console.log(value);

          const selectedValues: number[] = (value ?? []).map(Number);

          const isValid = requiredIds.every((id) =>
            selectedValues.includes(id)
          );

          if (isValid) return Promise.resolve();

          const requiredLabels = field?.options
            ?.filter((opt: any) => requiredIds.includes(opt.id))
            .map((opt: any) => opt.label)
            .join(", ");

          return Promise.reject(
            t("please-select-mandatory-options", {
              options: requiredLabels,
            })
          );
        },
      });
    }

    (field.validation_rules || []).forEach((ruleObj) => {
      const {
        rule,
        value,
        value_date,
        value_time,
        max_file_size,
        allowed_mimes,
        allowed_mimes_string,
        start_date,
        end_date,
        start_time,
        end_time,
      } = ruleObj;

      if (rule === "email") {
        rules.push({ type: "email", message: t("please-enter-a-valid-email") });
      }

      if (rule === "url") {
        rules.push({ type: "url", message: t("please-enter-a-valid-URL") });
      }

      if (rule === "min" && value) {
        rules.push({
          min: Number(value),
          ...(field?.type === "number" && {
            transform: (v: any) => (v === "" || v == null ? v : Number(v)),
          }),
          message:
            field.type === "number"
              ? t("min-value", { value })
              : t("min-length", { value }),
        });
      }

      if (rule === "max" && value) {
        rules.push({
          max: Number(value),
          ...(field?.type === "number" && {
            transform: (v: any) => (v === "" || v == null ? v : Number(v)),
          }),
          message:
            field.type === "number"
              ? t("max-value", { value })
              : t("max-length", { value }),
        });
      }

      if (rule === "regex" && value) {
        try {
          rules.push({
            pattern: new RegExp(value),
            message: t("not-match-pattern"),
          });
        } catch {
          // invalid pattern ignored
        }
      }

      if (rule === "numeric") {
        rules.push({
          validator: (_: any, val: any) => {
            if (!val) return Promise.resolve();
            const number = Number(val);
            return Number.isInteger(number)
              ? Promise.resolve()
              : Promise.reject(t("accept-only-numeric"));
          },
        });
      }

      if (field.type === "team") {
        const defaultMaxVal = 6;
        const defaultMinVal = 2;

        const isValidNumber = value && !isNaN(value);
        const adjustedMaxVal = isValidNumber ? value - 1 : defaultMaxVal - 1;
        const adjustedMinVal = isValidNumber
          ? value > 2
            ? value - 1
            : 1
          : defaultMinVal - 1;

        rules.push({
          validator: async (_: any, values: any) => {
            if (!values || !values.length) return Promise.resolve();

            if (
              rule === "max_team_members" &&
              Array.isArray(values) &&
              values.length > adjustedMaxVal
            ) {
              return Promise.reject(
                t("the-maximum-number-of-team-members-is", {
                  value: value || defaultMaxVal,
                })
              );
            }

            if (
              rule === "min_team_members" &&
              Array.isArray(values) &&
              values.length < adjustedMinVal
            ) {
              return Promise.reject(
                t("the-minimum-number-of-team-members-is", {
                  value: value || defaultMinVal,
                })
              );
            }

            return Promise.resolve();
          },
        });
      }

      if (field.type === "file") {
        if (max_file_size) {
          rules.push({
            validator: (_: any, fileList: any[]) => {
              if (!fileList?.length) return Promise.resolve();
              const file = fileList[0];
              const maxSizeBytes = Number(max_file_size) * 1024 * 1024;
              if (file && file.size > maxSizeBytes) {
                return Promise.reject(
                  t("uploaded-file-must-not-exceed", {
                    number: max_file_size,
                  })
                );
              }
              return Promise.resolve();
            },
          });
        }
      }

      if (field.type === "date") {
        rules.push({
          validator: (_: any, val: any) => {
            if (!val) return Promise.resolve();
            const inputDate = dayjs(val);

            if (
              rule === "after" &&
              value_date &&
              !inputDate.isAfter(dayjs(value_date), "day")
            ) {
              return Promise.reject(
                t("date-must-be-after", { date: value_date })
              );
            }

            if (
              rule === "before" &&
              value_date &&
              !inputDate.isBefore(dayjs(value_date), "day")
            ) {
              return Promise.reject(
                t("date-must-be-before", { date: value_date })
              );
            }

            if (
              rule === "after_or_equal" &&
              value_date &&
              !inputDate.isSameOrAfter(dayjs(value_date), "day")
            ) {
              return Promise.reject(
                t("date-must-be-on-or-after", { date: value_date })
              );
            }

            if (
              rule === "before_or_equal" &&
              value_date &&
              !inputDate.isSameOrBefore(dayjs(value_date), "day")
            ) {
              return Promise.reject(
                t("date-must-be-on-or-before", { date: value_date })
              );
            }

            if (
              rule === "between" &&
              start_date &&
              end_date &&
              !inputDate.isBetween(
                dayjs(start_date),
                dayjs(end_date),
                "day",
                "[]"
              )
            ) {
              return Promise.reject(
                t("date-must-be-between", {
                  start: start_date,
                  end: end_date,
                })
              );
            }

            return Promise.resolve();
          },
        });
      }

      if (field.type === "time") {
        const timeFormat = "HH:mm";
        const displayFormat = "h:mm A";

        if (rule === "after_time" && value_time) {
          rules.push({
            validator: (_: any, value: string) => {
              if (!value) return Promise.resolve();
              const inputTime = dayjs(value, timeFormat);
              const targetTime = dayjs(value_time, timeFormat);
              return inputTime.isAfter(targetTime)
                ? Promise.resolve()
                : Promise.reject(
                    t("time-must-be-after", {
                      time: targetTime.format(displayFormat),
                    })
                  );
            },
          });
        }

        if (rule === "before_time" && value_time) {
          rules.push({
            validator: (_: any, value: string) => {
              if (!value) return Promise.resolve();
              const inputTime = dayjs(value, timeFormat);
              const targetTime = dayjs(value_time, timeFormat);
              return inputTime.isBefore(targetTime)
                ? Promise.resolve()
                : Promise.reject(
                    t("time-must-be-before", {
                      time: targetTime.format(displayFormat),
                    })
                  );
            },
          });
        }

        if (rule === "between_time" && start_time && end_time) {
          rules.push({
            validator: (_: any, value: string) => {
              if (!value) return Promise.resolve();
              const inputTime = dayjs(value, timeFormat);
              const start = dayjs(start_time, timeFormat);
              const end = dayjs(end_time, timeFormat);
              return inputTime.isAfter(start) && inputTime.isBefore(end)
                ? Promise.resolve()
                : Promise.reject(
                    t("time-must-be-between", {
                      start: start.format(displayFormat),
                      end: end.format(displayFormat),
                    })
                  );
            },
          });
        }
      }
    });

    return rules;
  }

  return { getValidationRules };
}
