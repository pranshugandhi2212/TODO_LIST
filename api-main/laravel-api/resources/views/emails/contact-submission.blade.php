<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #eff5ff; font-family: Arial, Helvetica, sans-serif; color: #172033;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0;">
        New contact request from {{ $contactData['name'] }} about {{ $contactData['subject'] }}.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff5ff; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 720px; background-color: #ffffff; border-radius: 24px; overflow: hidden;">
                    <tr>
                        <td style="padding: 32px; background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #60a5fa 100%);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align: top;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 64px; height: 64px; border-radius: 18px; background-color: rgba(255, 255, 255, 0.12); text-align: center; vertical-align: middle;">
                                                    <svg width="40" height="40" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                                        <path d="M16 12C18.6 12 20.7 14.1 20.7 16.7V31.2L29.8 22.1C31.7 20.2 34.8 20.2 36.7 22.1C38.6 24 38.6 27.1 36.7 29L25.7 40V47.3C25.7 49.9 23.6 52 21 52C18.4 52 16.3 49.9 16.3 47.3V37.6L8.4 29.7C6.5 27.8 6.5 24.7 8.4 22.8C10.3 20.9 13.4 20.9 15.3 22.8L16 23.5V16.7C16 14.1 18.1 12 20.7 12H16Z" fill="white"/>
                                                        <path d="M42.8 46.7C41.6 46.7 40.4 46.2 39.4 45.3L34.4 40.5C32.5 38.7 32.4 35.6 34.2 33.7C36 31.8 39.1 31.7 41 33.5L42.4 34.8L49.9 27.3C51.8 25.4 54.9 25.4 56.8 27.3C58.7 29.2 58.7 32.3 56.8 34.2L46.3 44.7C45.4 45.6 44.1 46.7 42.8 46.7Z" fill="#B5D1FF"/>
                                                    </svg>
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255, 255, 255, 0.72);">Website Contact Alert</div>
                                                    <div style="margin-top: 6px; font-size: 28px; line-height: 1.2; font-weight: 800; color: #ffffff;">{{ $brandName }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" style="vertical-align: top; color: rgba(255, 255, 255, 0.76); font-size: 13px; line-height: 1.6;">
                                        <div>Submitted</div>
                                        <div style="font-weight: 700; color: #ffffff;">{{ $submittedAt }}</div>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top: 28px; padding: 24px; border-radius: 20px; background-color: rgba(255, 255, 255, 0.12);">
                                <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255, 255, 255, 0.74);">Subject</div>
                                <div style="margin-top: 10px; font-size: 30px; line-height: 1.2; font-weight: 800; color: #ffffff;">
                                    {{ $contactData['subject'] }}
                                </div>
                                <div style="margin-top: 12px; font-size: 16px; line-height: 1.6; color: rgba(255, 255, 255, 0.88);">
                                    {{ $contactData['name'] }} has reached out through your contact form. Reply directly to this email to continue the conversation.
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="padding-right: 10px; padding-bottom: 10px;" width="50%">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fbff; border: 1px solid #dbe7ff; border-radius: 18px;">
                                            <tr>
                                                <td style="padding: 18px;">
                                                    <div style="font-size: 12px; font-weight: 700; color: #5b6b83; text-transform: uppercase; letter-spacing: 0.08em;">Category</div>
                                                    <div style="margin-top: 8px; font-size: 18px; font-weight: 700; color: #172033;">{{ $categoryLabel }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="padding-left: 10px; padding-bottom: 10px;" width="50%">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fbff; border: 1px solid #dbe7ff; border-radius: 18px;">
                                            <tr>
                                                <td style="padding: 18px;">
                                                    <div style="font-size: 12px; font-weight: 700; color: #5b6b83; text-transform: uppercase; letter-spacing: 0.08em;">Priority</div>
                                                    <div style="margin-top: 10px;">
                                                        <span style="display: inline-block; padding: 8px 14px; border-radius: 999px; background-color: {{ $priorityBadgeColor }}; color: #ffffff; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                                                            {{ $priorityLabel }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0; border: 1px solid #e5edf9; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td colspan="2" style="padding: 18px 22px; background-color: #f8fbff; font-size: 16px; font-weight: 700; color: #172033;">Contact Details</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Full Name</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Email</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">
                                        <a href="mailto:{{ $contactData['email'] }}" style="color: #2563eb; text-decoration: none;">{{ $contactData['email'] }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Phone</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['phone'] ?: 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Company</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['company'] ?: 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Job Title</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['jobTitle'] ?: 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Newsletter</td>
                                    <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['subscribe'] ? 'Subscribed' : 'No subscription' }}</td>
                                </tr>
                                @if(! empty($contactData['rating']))
                                    <tr>
                                        <td style="padding: 16px 22px; width: 180px; border-top: 1px solid #e5edf9; font-size: 13px; font-weight: 700; color: #5b6b83;">Rating</td>
                                        <td style="padding: 16px 22px; border-top: 1px solid #e5edf9; font-size: 15px; color: #172033;">{{ $contactData['rating'] }}/5</td>
                                    </tr>
                                @endif
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px; border: 1px solid #e5edf9; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 18px 22px; background-color: #f8fbff; font-size: 16px; font-weight: 700; color: #172033;">Message</td>
                                </tr>
                                <tr>
                                    <td style="padding: 22px; font-size: 15px; line-height: 1.75; color: #243246;">
                                        {!! nl2br(e($contactData['message'])) !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 32px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #172033; border-radius: 18px;">
                                <tr>
                                    <td style="padding: 20px 22px; font-size: 14px; line-height: 1.7; color: rgba(255, 255, 255, 0.82);">
                                        <strong style="display: block; margin-bottom: 4px; color: #ffffff;">Quick reply tip</strong>
                                        You can respond directly to this email and your message will go to {{ $contactData['name'] }} at {{ $contactData['email'] }}.
                                        @if($supportEmail)
                                            <br>
                                            Support inbox: <a href="mailto:{{ $supportEmail }}" style="color: #93c5fd; text-decoration: none;">{{ $supportEmail }}</a>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
