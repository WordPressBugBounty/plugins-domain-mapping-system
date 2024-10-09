import {__} from "@wordpress/i18n";

export default function MediaUploader({isPremium, image, setImage, changed}) {
    /**
     * Upload image
     */
    const uploadImage = () => {
        if (!isPremium) {
            return;
        }

        // Crop controls
        const cropControl = {
            id: "control-id",
            params: {
                flex_width: false,  // set to true if the width of the cropped image can be different to the width defined here
                flex_height: false, // set to true if the height of the cropped image can be different to the height defined here
                width: 512,  // set the desired width of the destination image here
                height: 512, // set the desired height of the destination image here
            },
        };

        // Init media uploader
        const mediaUploader = wp.media({
            button: {
                text: __("Select", 'domain-mapping-system'),
                close: false
            },
            states: [
                new wp.media.controller.Library({
                    title: __("Select and Crop", 'domain-mapping-system'),
                    library: wp.media.query({type: 'image'}),
                    multiple: false,
                    date: false,
                    priority: 20,
                    suggestedWidth: 512,
                    suggestedHeight: 512
                }),
                new wp.media.controller.CustomizeImageCropper({
                    imgSelectOptions: (attachment, controller) => {
                        const realWidth = attachment.get('width'), realHeight = attachment.get('height');
                        return {
                            handles: true,
                            keys: true,
                            instance: true,
                            persistent: true,
                            imageWidth: realWidth,
                            imageHeight: realHeight,
                            minWidth: attachment.get('width') < 512 ? attachment.get('width') : 512,
                            minHeight: attachment.get('height') < 512 ? attachment.get('height') : 512,
                            x1: 0,
                            y1: 0,
                            x2: realWidth,
                            y2: realHeight
                        };
                    },
                    control: cropControl,
                })
            ]
        });

        // Crop action handler
        mediaUploader.on('cropped', (croppedImage) => {
            setImage({
                id: croppedImage.id,
                src: croppedImage.url,
            });
            changed();
        });

        // Select action handler
        mediaUploader.on("select", () => {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            if (cropControl.params.width === attachment.width && cropControl.params.height === attachment.height && !cropControl.params.flex_width && !cropControl.params.flex_height) {
                setImage({
                    id: attachment.id,
                    src: attachment.url,
                });
                changed();
                mediaUploader.close();
            } else {
                mediaUploader.setState('cropper');
            }
        });

        // Open media uploader modal
        mediaUploader.open();
    }

    /**
     * Delete image
     */
    const deleteImage = () => {
        setImage({id: false});
        changed();
    }

    return (
        <div className="dms-n-config-table-favicon">
            {!!image.id && <>
                <img className={image.classes || 'favicon'} src={image.src} alt="favicon"/>
                <button className="dms-delete-img" title="delete" onClick={deleteImage}>&times;</button>
            </>}
            <button className={"upload upload-btn" + (!isPremium ? ' disabled' : '')}
                    onClick={uploadImage}>{__("Upload Image", 'domain-mapping-system')}</button>
        </div>
    );
}